<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Model\Mapper;

use Formula\ZohoBooks\Model\Catalog\ProductCategoryClassifier;
use Formula\ZohoBooks\Model\Gst\HsnResolver;
use Formula\ZohoBooks\Model\Gst\PlaceOfSupplyResolver;
use Formula\ZohoBooks\Model\Gst\StateCodeMapper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;

/**
 * Pure mapper: builds a Zoho Books Invoice payload (POST /invoices) from a paid Magento
 * invoice + the Zoho contact id the caller has already upserted (upsert is a later task).
 *
 * Magento owns the invoice numbering series - `invoice_number` mirrors the Magento
 * increment id verbatim rather than letting Zoho assign its own, per the one-way-push /
 * Magento-is-source-of-truth design for this integration.
 *
 * Typed against the CONCRETE {@see Invoice} / {@see InvoiceItem} models (not just their API
 * interfaces) because place-of-supply and per-line HSN resolution need `Invoice::getOrder()`
 * and `InvoiceItem::getOrderItem()->getProduct()`, neither of which is declared on
 * `Magento\Sales\Api\Data\InvoiceInterface` / `InvoiceItemInterface`. In practice this is
 * exactly what a `sales_order_invoice_save_after` observer (the later push-trigger task)
 * receives, so this should not be a real constraint - flagging it as an explicit deviation
 * from the task's `InvoiceInterface`-typed signature for visibility.
 *
 * @todo Reconcile field names/shape (esp. tax_id vs. tax_percentage - see below) against
 *       Zoho Books API v3 + org config when P2/P3 lands. NOT verified against a live org.
 */
class InvoiceMapper
{
    /**
     * @todo B2B GSTIN capture is out of scope for this task - see ContactMapper.
     */
    public const GST_TREATMENT_CONSUMER = 'consumer';

    private const FALLBACK_REASON_HSN_UNRESOLVED = 'hsn_resolver_fallback';
    private const FALLBACK_REASON_CATEGORIES_UNAVAILABLE = 'categories_unavailable';

    public function __construct(
        private readonly HsnResolver $hsnResolver,
        private readonly PlaceOfSupplyResolver $placeOfSupplyResolver,
        private readonly StateCodeMapper $stateCodeMapper,
        private readonly ProductCategoryClassifier $productCategoryClassifier
    ) {
    }

    /**
     * @return array{payload: array, fallbackSkus: array<int, array{sku: string, name: string, reason: string}>}
     *
     * `payload` is exactly what should be sent to POST /invoices. `fallbackSkus` is NOT a
     * Zoho field and must never be merged into the payload sent to the API - it is a
     * separate, hard-to-miss signal for the later push layer to log/flag unclassified
     * products (per the B2 review note: never silently bury an HSN fallback).
     *
     * @throws LocalizedException when the order has no billing/shipping address, that
     *                            address's region cannot be resolved to a GST state, or the
     *                            invoice's created-at date is missing/unparseable
     */
    public function build(Invoice $invoice, string $zohoContactId): array
    {
        $order = $invoice->getOrder();
        $address = $order->getBillingAddress() ?? $order->getShippingAddress();
        if ($address === null) {
            throw new LocalizedException(__(
                'Invoice "%1" has no billing or shipping address on its order; cannot '
                . 'determine place of supply.',
                (string) $invoice->getIncrementId()
            ));
        }

        $customerStateCode = $this->stateCodeMapper->resolve($address->getRegionCode(), $address->getRegion());
        $placeOfSupply = $this->placeOfSupplyResolver->resolve($customerStateCode);

        $lineItems = [];
        $fallbackSkus = [];

        foreach ($invoice->getItems() as $invoiceItem) {
            [$hsnResult, $categoriesUnavailable] = $this->resolveLineHsn($invoiceItem);
            $taxSplit = $placeOfSupply->splitTax($hsnResult->rate);

            $sku = (string) $invoiceItem->getSku();
            $name = (string) $invoiceItem->getName();

            if ($hsnResult->isFallback || $categoriesUnavailable) {
                $fallbackSkus[] = [
                    'sku' => $sku,
                    'name' => $name,
                    'reason' => $categoriesUnavailable
                        ? self::FALLBACK_REASON_CATEGORIES_UNAVAILABLE
                        : self::FALLBACK_REASON_HSN_UNRESOLVED,
                ];
            }

            $lineItems[] = [
                'name' => $name,
                'sku' => $sku,
                'description' => (string) $invoiceItem->getDescription(),
                'rate' => (float) $invoiceItem->getPrice(),
                'quantity' => (float) $invoiceItem->getQty(),
                'hsn_or_sac' => $hsnResult->hsn,
                // @todo whether Zoho should receive tax_percentage (+ our own cgst/sgst/igst
                //       breakdown for our own auditing) or a tax_id referencing an
                //       org-configured Zoho tax record depends on how taxes are set up in
                //       the target Zoho org - flagging for P2, not assuming either way.
                'tax_percentage' => $hsnResult->rate,
                'tax_split' => $taxSplit,
            ];
        }

        return [
            'payload' => [
                'customer_id' => $zohoContactId,
                'invoice_number' => (string) $invoice->getIncrementId(),
                'date' => $this->formatDate($invoice->getCreatedAt()),
                'place_of_supply' => $placeOfSupply->placeOfSupply,
                'gst_treatment' => self::GST_TREATMENT_CONSUMER,
                'line_items' => $lineItems,
            ],
            'fallbackSkus' => $fallbackSkus,
        ];
    }

    /**
     * Resolve the HSN/rate for one invoice line, defensively.
     *
     * If the order item, its product, or the product's categories can't be reached, this
     * classifies the line as skincare (the same safe default HsnResolver itself uses) and
     * reports it as unavailable via the second tuple element - distinct from
     * HsnResult::$isFallback, which flags an unrecognized *bucket key* rather than a
     * missing product/category read. Both cases end up merged into the same fallbackSkus
     * list in build() since both mean "a human should check this line's HSN".
     *
     * @return array{0: \Formula\ZohoBooks\Model\Gst\HsnResult, 1: bool}
     */
    private function resolveLineHsn(InvoiceItem $invoiceItem): array
    {
        [$categoryNames, $categoriesUnavailable] = $this->resolveCategoryNames($invoiceItem);
        $bucket = $this->productCategoryClassifier->classify($categoryNames);

        return [$this->hsnResolver->resolve($bucket), $categoriesUnavailable];
    }

    /**
     * @return array{0: string[], 1: bool} category names, and whether they were unavailable
     */
    private function resolveCategoryNames(InvoiceItem $invoiceItem): array
    {
        try {
            $orderItem = $invoiceItem->getOrderItem();
            if ($orderItem === null) {
                return [[], true];
            }

            $product = $orderItem->getProduct();
            if ($product === null) {
                return [[], true];
            }

            $names = [];
            foreach ($product->getCategoryCollection() as $category) {
                $names[] = (string) $category->getName();
            }

            return [$names, false];
        } catch (\Throwable $e) {
            // Genuinely can't tell what this product is - flag it rather than fatal the
            // whole invoice push over one unresolvable line.
            return [[], true];
        }
    }

    private function formatDate(?string $createdAt): string
    {
        if (!$createdAt) {
            throw new LocalizedException(__('Invoice created-at date is missing; cannot build Zoho invoice date.'));
        }

        try {
            return (new \DateTimeImmutable($createdAt))->format('Y-m-d');
        } catch (\Exception $e) {
            throw new LocalizedException(__('Invoice created-at date "%1" could not be parsed.', $createdAt));
        }
    }
}
