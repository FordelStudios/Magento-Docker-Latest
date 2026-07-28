<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Model\Request;

use Formula\Shadowfax\Helper\Data as ShadowfaxHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Maps a Magento order into a ShadowFax create-shipment "manifest" JSON payload.
 *
 * IMPORTANT — ASSUMED SCHEMA, NOT VERIFIED AGAINST REAL SHADOWFAX DOCS:
 * The real ShadowFax merchant API contract is behind their login and we do not have
 * credentials or their API documentation yet. Everything below is our best-guess
 * mapping based on typical Indian courier-aggregator (Shiprocket/Shadowfax-adjacent)
 * payload shapes. This class is unit-tested against the ASSUMED schema only — that
 * proves the transformation LOGIC is correct, not that it matches the real ShadowFax
 * contract.
 *
 * Assumed payload shape:
 * {
 *   "client_order_id": "<order increment id>",
 *   "client_name": "<merchant client identifier registered with ShadowFax>",
 *   "order_value": <grand total>,
 *   "payment_type": "COD" | "PREPAID",
 *   "cod_amount": <grand total if COD else 0>,
 *   "pickup_details": { "name","phone","address","city","state","pincode" },
 *   "drop_details":   { "name","phone","address","city","state","pincode" },
 *   "package": {
 *      "weight": <total order weight>,
 *      "items": [ { "name","sku","quantity","price","hsn" } ]
 *   }
 * }
 *
 * @todo Reconcile field names against real ShadowFax API docs when P1 credentials land.
 */
class ManifestPayloadBuilder
{
    public const PAYMENT_TYPE_COD = 'COD';
    public const PAYMENT_TYPE_PREPAID = 'PREPAID';

    private const PAYMENT_METHOD_COD = 'cashondelivery';

    /**
     * Fallback HSN code used because there is no per-product HSN attribute in this
     * codebase yet. Do NOT treat this as a real HSN — it is a visible placeholder.
     *
     * @todo GST/HSN-per-product mapping is a separate Zoho CR; wire the real value in
     *       once that attribute/data exists and remove this fallback.
     */
    public const DEFAULT_HSN = '000000';

    /**
     * @var ShadowfaxHelper
     */
    private $shadowfaxHelper;

    /**
     * All pickup details (name, phone, address, city, state, pincode) are read from the
     * ShadowFax admin config via Helper\Data. This keeps build() deterministic (given
     * fixed config it produces a fixed payload) while guaranteeing we never silently
     * ship a manifest with an empty pickup address — the values come from a single
     * source of truth the merchant configures once.
     *
     * @param ShadowfaxHelper $shadowfaxHelper
     */
    public function __construct(
        ShadowfaxHelper $shadowfaxHelper
    ) {
        $this->shadowfaxHelper = $shadowfaxHelper;
    }

    /**
     * Pure mapping: Magento order -> assumed ShadowFax manifest payload.
     * No network calls, no side effects.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function build(OrderInterface $order): array
    {
        $isCod = $this->isCashOnDelivery($order);
        $grandTotal = (float) $order->getGrandTotal();

        return [
            'client_order_id' => $order->getIncrementId(),
            'client_name' => $this->shadowfaxHelper->getClientName(),
            'order_value' => $grandTotal,
            'payment_type' => $isCod ? self::PAYMENT_TYPE_COD : self::PAYMENT_TYPE_PREPAID,
            'cod_amount' => $isCod ? $grandTotal : 0.0,
            'pickup_details' => $this->buildPickupDetails(),
            'drop_details' => $this->buildDropDetails($order),
            'package' => $this->buildPackage($order),
        ];
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    private function isCashOnDelivery(OrderInterface $order): bool
    {
        $payment = $order->getPayment();

        return $payment !== null && $payment->getMethod() === self::PAYMENT_METHOD_COD;
    }

    /**
     * @return array
     */
    private function buildPickupDetails(): array
    {
        return [
            'name' => (string) $this->shadowfaxHelper->getPickupLocation(),
            'phone' => (string) $this->shadowfaxHelper->getPickupPhone(),
            'address' => (string) $this->shadowfaxHelper->getPickupAddress(),
            'city' => (string) $this->shadowfaxHelper->getPickupCity(),
            'state' => (string) $this->shadowfaxHelper->getPickupState(),
            'pincode' => (string) $this->shadowfaxHelper->getPickupPostcode(),
        ];
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function buildDropDetails(OrderInterface $order): array
    {
        // getShippingAddress() is a Magento\Sales\Model\Order method, not declared on
        // OrderInterface itself — same loose-typing pattern Formula_Shiprocket uses.
        $address = method_exists($order, 'getShippingAddress') ? $order->getShippingAddress() : null;

        if ($address === null) {
            return [
                'name' => '',
                'phone' => '',
                'address' => '',
                'city' => '',
                'state' => '',
                'pincode' => '',
            ];
        }

        $name = trim(((string) $address->getFirstname()) . ' ' . ((string) $address->getLastname()));
        $street = $address->getStreet();

        return [
            'name' => $name,
            'phone' => (string) $address->getTelephone(),
            'address' => is_array($street) ? implode(', ', $street) : (string) $street,
            'city' => (string) $address->getCity(),
            'state' => (string) $address->getRegion(),
            'pincode' => (string) $address->getPostcode(),
        ];
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    private function buildPackage(OrderInterface $order): array
    {
        // getAllVisibleItems() is a Magento\Sales\Model\Order method, not declared on
        // OrderInterface itself — same loose-typing pattern Formula_Shiprocket uses.
        $visibleItems = method_exists($order, 'getAllVisibleItems') ? $order->getAllVisibleItems() : [];

        $items = [];
        foreach ($visibleItems as $item) {
            $items[] = $this->buildItem($item);
        }

        return [
            'weight' => (float) $order->getWeight(),
            'items' => $items,
        ];
    }

    /**
     * @param OrderItemInterface $item
     * @return array
     */
    private function buildItem(OrderItemInterface $item): array
    {
        return [
            'name' => $item->getName(),
            'sku' => $item->getSku(),
            'quantity' => (int) round((float) $item->getQtyOrdered()),
            'price' => (float) $item->getPrice(),
            'hsn' => self::DEFAULT_HSN,
        ];
    }
}
