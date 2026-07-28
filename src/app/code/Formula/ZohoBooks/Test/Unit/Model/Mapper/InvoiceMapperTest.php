<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Test\Unit\Model\Mapper;

use Formula\ZohoBooks\Helper\Data as ZohoBooksHelper;
use Formula\ZohoBooks\Model\Catalog\ProductCategoryClassifier;
use Formula\ZohoBooks\Model\Gst\HsnResolver;
use Formula\ZohoBooks\Model\Gst\PlaceOfSupplyResolver;
use Formula\ZohoBooks\Model\Gst\StateCodeMapper;
use Formula\ZohoBooks\Model\Mapper\InvoiceMapper;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InvoiceMapperTest extends TestCase
{
    private ZohoBooksHelper|MockObject $helper;
    private InvoiceMapper $mapper;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(ZohoBooksHelper::class);
        $this->helper->method('getOrgGstStateCode')->willReturn('27'); // org = Maharashtra

        // HsnResolver, PlaceOfSupplyResolver, StateCodeMapper and ProductCategoryClassifier
        // are all pure - use the real implementations so the tax-split math is genuinely
        // exercised end to end, not just "did we call the mock right".
        $this->mapper = new InvoiceMapper(
            new HsnResolver(),
            new PlaceOfSupplyResolver($this->helper),
            new StateCodeMapper(),
            new ProductCategoryClassifier()
        );
    }

    private function makeBillingAddress(string $regionCode, string $regionName): OrderAddress|MockObject
    {
        $address = $this->createMock(OrderAddress::class);
        $address->method('getRegionCode')->willReturn($regionCode);
        $address->method('getRegion')->willReturn($regionName);

        return $address;
    }

    private function makeProduct(array $categoryNames): Product|MockObject
    {
        $product = $this->createMock(Product::class);
        $categories = array_map(function (string $name) {
            $category = $this->getMockBuilder(\stdClass::class)->addMethods(['getName'])->getMock();
            $category->method('getName')->willReturn($name);

            return $category;
        }, $categoryNames);
        $product->method('getCategoryCollection')->willReturn($categories);

        return $product;
    }

    private function makeInvoiceItem(
        string $sku,
        string $name,
        float $price,
        float $qty,
        ?Product $product
    ): InvoiceItem|MockObject {
        $orderItem = $this->createMock(OrderItem::class);
        $orderItem->method('getProduct')->willReturn($product);

        $item = $this->createMock(InvoiceItem::class);
        $item->method('getSku')->willReturn($sku);
        $item->method('getName')->willReturn($name);
        $item->method('getDescription')->willReturn($name);
        $item->method('getPrice')->willReturn($price);
        $item->method('getQty')->willReturn($qty);
        $item->method('getOrderItem')->willReturn($product === null ? null : $orderItem);

        return $item;
    }

    private function makeInvoice(
        OrderAddress $billingAddress,
        array $items,
        string $incrementId = '000000123',
        string $createdAt = '2026-07-20 10:15:00'
    ): Invoice|MockObject {
        $order = $this->createMock(Order::class);
        $order->method('getBillingAddress')->willReturn($billingAddress);
        $order->method('getShippingAddress')->willReturn(null);

        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getOrder')->willReturn($order);
        $invoice->method('getIncrementId')->willReturn($incrementId);
        $invoice->method('getCreatedAt')->willReturn($createdAt);
        $invoice->method('getItems')->willReturn($items);

        return $invoice;
    }

    public function testBuildMapsIntrastateInvoiceWithCgstSgstSplit(): void
    {
        // Customer also in Maharashtra (27) -> intrastate -> CGST+SGST.
        $billingAddress = $this->makeBillingAddress('MH', 'Maharashtra');
        $skincareProduct = $this->makeProduct(['Best Sellers']);
        $haircareProduct = $this->makeProduct(['Hair Care']);

        $items = [
            $this->makeInvoiceItem('SKU-1', 'Glow Serum', 1000.0, 2.0, $skincareProduct),
            $this->makeInvoiceItem('SKU-2', 'Hair Oil', 500.0, 1.0, $haircareProduct),
        ];

        $invoice = $this->makeInvoice($billingAddress, $items);

        $result = $this->mapper->build($invoice, 'zoho-contact-123');

        $payload = $result['payload'];
        $this->assertSame('zoho-contact-123', $payload['customer_id']);
        $this->assertSame('000000123', $payload['invoice_number']);
        $this->assertSame('2026-07-20', $payload['date']);
        $this->assertSame('27', $payload['place_of_supply']);
        $this->assertSame(InvoiceMapper::GST_TREATMENT_CONSUMER, $payload['gst_treatment']);

        $this->assertCount(2, $payload['line_items']);

        $line1 = $payload['line_items'][0];
        $this->assertSame('SKU-1', $line1['sku']);
        $this->assertSame('3304', $line1['hsn_or_sac']); // skincare
        $this->assertSame(18.0, $line1['tax_percentage']);
        $this->assertSame(['cgst' => 9.0, 'sgst' => 9.0], $line1['tax_split']);
        $this->assertSame(1000.0, $line1['rate']);
        $this->assertSame(2.0, $line1['quantity']);

        $line2 = $payload['line_items'][1];
        $this->assertSame('SKU-2', $line2['sku']);
        $this->assertSame('3305', $line2['hsn_or_sac']); // haircare
        $this->assertSame(['cgst' => 9.0, 'sgst' => 9.0], $line2['tax_split']);

        $this->assertSame([], $result['fallbackSkus']);
    }

    public function testBuildMapsInterstateInvoiceWithIgst(): void
    {
        // Org is Maharashtra (27); customer is Delhi (07) -> interstate -> IGST.
        $billingAddress = $this->makeBillingAddress('DL', 'Delhi');
        $product = $this->makeProduct(['Bath Soap']);

        $items = [$this->makeInvoiceItem('SKU-SOAP', 'Neem Soap', 200.0, 3.0, $product)];

        $invoice = $this->makeInvoice($billingAddress, $items);

        $result = $this->mapper->build($invoice, 'zoho-contact-456');

        $this->assertSame('07', $result['payload']['place_of_supply']);
        $line = $result['payload']['line_items'][0];
        $this->assertSame('3401', $line['hsn_or_sac']); // soap
        $this->assertSame(['igst' => 18.0], $line['tax_split']);
    }

    public function testBuildFlagsFallbackSkuWhenProductIsUnavailable(): void
    {
        $billingAddress = $this->makeBillingAddress('MH', 'Maharashtra');
        $items = [$this->makeInvoiceItem('SKU-MISSING', 'Mystery Item', 100.0, 1.0, null)];

        $invoice = $this->makeInvoice($billingAddress, $items);

        $result = $this->mapper->build($invoice, 'zoho-contact-789');

        // Product/categories unavailable -> classified as skincare (safe default) but flagged.
        $line = $result['payload']['line_items'][0];
        $this->assertSame('3304', $line['hsn_or_sac']);

        $this->assertCount(1, $result['fallbackSkus']);
        $this->assertSame('SKU-MISSING', $result['fallbackSkus'][0]['sku']);
        $this->assertSame('categories_unavailable', $result['fallbackSkus'][0]['reason']);
    }

    public function testBuildDoesNotFlagProductsWithNoMatchingCategoryAsFallback(): void
    {
        // Categories loaded fine, they just don't match hair/soap keywords - this is a
        // normal skincare classification, not an error condition, so it must NOT be
        // reported alongside genuine unclassifiable-product fallbacks.
        $billingAddress = $this->makeBillingAddress('MH', 'Maharashtra');
        $product = $this->makeProduct(['New Arrivals']);
        $items = [$this->makeInvoiceItem('SKU-OK', 'Moisturizer', 300.0, 1.0, $product)];

        $invoice = $this->makeInvoice($billingAddress, $items);

        $result = $this->mapper->build($invoice, 'zoho-contact-999');

        $this->assertSame([], $result['fallbackSkus']);
    }

    public function testBuildThrowsWhenNoBillingOrShippingAddressExists(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getBillingAddress')->willReturn(null);
        $order->method('getShippingAddress')->willReturn(null);

        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getOrder')->willReturn($order);

        $this->expectException(LocalizedException::class);

        $this->mapper->build($invoice, 'zoho-contact-1');
    }

    public function testBuildFallsBackToShippingAddressWhenBillingAddressIsMissing(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getBillingAddress')->willReturn(null);
        $shippingAddress = $this->makeBillingAddress('KA', 'Karnataka');
        $order->method('getShippingAddress')->willReturn($shippingAddress);

        $invoice = $this->createMock(Invoice::class);
        $invoice->method('getOrder')->willReturn($order);
        $invoice->method('getIncrementId')->willReturn('000000200');
        $invoice->method('getCreatedAt')->willReturn('2026-07-21 09:00:00');
        $invoice->method('getItems')->willReturn([]);

        $result = $this->mapper->build($invoice, 'zoho-contact-2');

        $this->assertSame('29', $result['payload']['place_of_supply']);
    }

    public function testBuildThrowsWhenInvoiceCreatedAtIsMissing(): void
    {
        $billingAddress = $this->makeBillingAddress('MH', 'Maharashtra');
        $invoice = $this->makeInvoice($billingAddress, [], '000000300', '');

        $this->expectException(LocalizedException::class);

        $this->mapper->build($invoice, 'zoho-contact-3');
    }
}
