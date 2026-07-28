<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Test\Unit\Model\Request;

use Formula\Shadowfax\Helper\Data as ShadowfaxHelper;
use Formula\Shadowfax\Model\Request\ManifestPayloadBuilder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the pure order -> ShadowFax manifest payload mapping.
 *
 * NOTE: these tests verify the mapping LOGIC against our ASSUMED ShadowFax schema
 * (see ManifestPayloadBuilder class docblock). They do not, and cannot, verify the
 * mapping against the real ShadowFax API — we do not have credentials/docs yet.
 */
class ManifestPayloadBuilderTest extends TestCase
{
    private const PICKUP_LOCATION = 'Formula Warehouse';
    private const PICKUP_POSTCODE = '560001';
    private const PICKUP_PHONE = '9999999999';
    private const PICKUP_ADDRESS = 'Plot 1, Industrial Area';
    private const PICKUP_CITY = 'Bengaluru';
    private const PICKUP_STATE = 'Karnataka';
    private const CLIENT_NAME = 'formula-shop';

    /**
     * @var MockObject|ShadowfaxHelper
     */
    private $shadowfaxHelper;

    private ManifestPayloadBuilder $builder;

    protected function setUp(): void
    {
        $this->shadowfaxHelper = $this->getMockBuilder(ShadowfaxHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shadowfaxHelper->method('getPickupLocation')->willReturn(self::PICKUP_LOCATION);
        $this->shadowfaxHelper->method('getPickupPostcode')->willReturn(self::PICKUP_POSTCODE);
        $this->shadowfaxHelper->method('getPickupPhone')->willReturn(self::PICKUP_PHONE);
        $this->shadowfaxHelper->method('getPickupAddress')->willReturn(self::PICKUP_ADDRESS);
        $this->shadowfaxHelper->method('getPickupCity')->willReturn(self::PICKUP_CITY);
        $this->shadowfaxHelper->method('getPickupState')->willReturn(self::PICKUP_STATE);
        $this->shadowfaxHelper->method('getClientName')->willReturn(self::CLIENT_NAME);

        $this->builder = new ManifestPayloadBuilder($this->shadowfaxHelper);
    }

    /**
     * @param string $paymentMethod
     * @param float $grandTotal
     * @param float $weight
     * @return MockObject|Order
     */
    private function buildOrderMock(
        string $paymentMethod,
        float $grandTotal,
        float $weight,
        array $items
    ) {
        $payment = $this->getMockBuilder(OrderPayment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $payment->method('getMethod')->willReturn($paymentMethod);

        $address = $this->getMockBuilder(OrderAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
        $address->method('getFirstname')->willReturn('Jane');
        $address->method('getLastname')->willReturn('Doe');
        $address->method('getTelephone')->willReturn('9876543210');
        $address->method('getStreet')->willReturn(['221B Baker Street']);
        $address->method('getCity')->willReturn('Mumbai');
        $address->method('getRegion')->willReturn('Maharashtra');
        $address->method('getPostcode')->willReturn('400001');

        $orderItems = [];
        foreach ($items as $item) {
            $mockItem = $this->getMockBuilder(OrderItem::class)
                ->disableOriginalConstructor()
                ->getMock();
            $mockItem->method('getName')->willReturn($item['name']);
            $mockItem->method('getSku')->willReturn($item['sku']);
            $mockItem->method('getQtyOrdered')->willReturn($item['qty']);
            $mockItem->method('getPrice')->willReturn($item['price']);
            $orderItems[] = $mockItem;
        }

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $order->method('getIncrementId')->willReturn('000000123');
        $order->method('getGrandTotal')->willReturn($grandTotal);
        $order->method('getWeight')->willReturn($weight);
        $order->method('getPayment')->willReturn($payment);
        $order->method('getShippingAddress')->willReturn($address);
        $order->method('getAllVisibleItems')->willReturn($orderItems);

        return $order;
    }

    public function testCodOrderMapsPaymentTypeAndCodAmount(): void
    {
        $order = $this->buildOrderMock('cashondelivery', 1500.50, 1.2, [
            ['name' => 'Face Wash', 'sku' => 'FW-100', 'qty' => 1.0, 'price' => 1500.50],
        ]);

        $payload = $this->builder->build($order);

        $this->assertSame('COD', $payload['payment_type']);
        $this->assertSame(1500.50, $payload['cod_amount']);
        $this->assertSame(1500.50, $payload['order_value']);
    }

    public function testPrepaidOrderMapsPaymentTypeAndZeroCodAmount(): void
    {
        $order = $this->buildOrderMock('razorpay', 999.00, 0.5, [
            ['name' => 'Serum', 'sku' => 'SR-200', 'qty' => 1.0, 'price' => 999.00],
        ]);

        $payload = $this->builder->build($order);

        $this->assertSame('PREPAID', $payload['payment_type']);
        $this->assertSame(0.0, $payload['cod_amount']);
        $this->assertSame(999.00, $payload['order_value']);
    }

    public function testItemsAreMappedWithNameSkuQtyAndPrice(): void
    {
        $order = $this->buildOrderMock('razorpay', 2000.00, 1.0, [
            ['name' => 'Face Wash', 'sku' => 'FW-100', 'qty' => 2.0, 'price' => 500.00],
            ['name' => 'Serum', 'sku' => 'SR-200', 'qty' => 1.0, 'price' => 1000.00],
        ]);

        $payload = $this->builder->build($order);

        $this->assertCount(2, $payload['package']['items']);

        $this->assertSame('Face Wash', $payload['package']['items'][0]['name']);
        $this->assertSame('FW-100', $payload['package']['items'][0]['sku']);
        $this->assertSame(2, $payload['package']['items'][0]['quantity']);
        $this->assertSame(500.00, $payload['package']['items'][0]['price']);
        $this->assertSame(
            ManifestPayloadBuilder::DEFAULT_HSN,
            $payload['package']['items'][0]['hsn']
        );

        $this->assertSame('Serum', $payload['package']['items'][1]['name']);
        $this->assertSame('SR-200', $payload['package']['items'][1]['sku']);
    }

    public function testFractionalQtyIsRoundedNotTruncated(): void
    {
        // 1.5 units must round to 2, never truncate to 1 (under-declaring logistics units).
        $order = $this->buildOrderMock('razorpay', 750.00, 1.0, [
            ['name' => 'Sheet Mask', 'sku' => 'SM-400', 'qty' => 1.5, 'price' => 500.00],
        ]);

        $payload = $this->builder->build($order);

        $this->assertSame(2, $payload['package']['items'][0]['quantity']);
    }

    public function testDropDetailsArePulledFromShippingAddress(): void
    {
        $order = $this->buildOrderMock('razorpay', 100.00, 0.2, [
            ['name' => 'Toner', 'sku' => 'TN-300', 'qty' => 1.0, 'price' => 100.00],
        ]);

        $payload = $this->builder->build($order);

        $this->assertSame('Jane Doe', $payload['drop_details']['name']);
        $this->assertSame('9876543210', $payload['drop_details']['phone']);
        $this->assertSame('221B Baker Street', $payload['drop_details']['address']);
        $this->assertSame('Mumbai', $payload['drop_details']['city']);
        $this->assertSame('Maharashtra', $payload['drop_details']['state']);
        $this->assertSame('400001', $payload['drop_details']['pincode']);
    }

    public function testPickupDetailsArePulledFromInjectedConfig(): void
    {
        $order = $this->buildOrderMock('razorpay', 100.00, 0.2, [
            ['name' => 'Toner', 'sku' => 'TN-300', 'qty' => 1.0, 'price' => 100.00],
        ]);

        $payload = $this->builder->build($order);

        $this->assertSame(self::PICKUP_LOCATION, $payload['pickup_details']['name']);
        $this->assertSame(self::PICKUP_PHONE, $payload['pickup_details']['phone']);
        $this->assertSame(self::PICKUP_ADDRESS, $payload['pickup_details']['address']);
        $this->assertSame(self::PICKUP_CITY, $payload['pickup_details']['city']);
        $this->assertSame(self::PICKUP_STATE, $payload['pickup_details']['state']);
        $this->assertSame(self::PICKUP_POSTCODE, $payload['pickup_details']['pincode']);
        $this->assertSame(self::CLIENT_NAME, $payload['client_name']);
    }

    public function testClientOrderIdMapsToOrderIncrementId(): void
    {
        $order = $this->buildOrderMock('razorpay', 100.00, 0.2, [
            ['name' => 'Toner', 'sku' => 'TN-300', 'qty' => 1.0, 'price' => 100.00],
        ]);

        $payload = $this->builder->build($order);

        $this->assertSame('000000123', $payload['client_order_id']);
    }

    public function testMissingShippingAddressDoesNotFatal(): void
    {
        $order = $this->buildOrderMock('razorpay', 100.00, 0.2, []);
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $order->method('getIncrementId')->willReturn('000000999');
        $order->method('getGrandTotal')->willReturn(100.00);
        $order->method('getWeight')->willReturn(0.2);
        $order->method('getPayment')->willReturn(null);
        $order->method('getShippingAddress')->willReturn(null);
        $order->method('getAllVisibleItems')->willReturn([]);

        $payload = $this->builder->build($order);

        $this->assertSame('PREPAID', $payload['payment_type']);
        $this->assertSame('', $payload['drop_details']['name']);
        $this->assertSame([], $payload['package']['items']);
    }
}
