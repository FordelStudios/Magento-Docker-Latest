<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Test\Unit\Model;

use Formula\DeliveryPartner\Api\Data\DeliveryShipmentResultInterface;
use Formula\DeliveryPartner\Model\Data\DeliveryShipmentResult;
use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\DeliveryPartner\Model\ShipmentResultPersister;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ShipmentResultPersister.
 *
 * The order is mocked via the concrete \Magento\Sales\Model\Order class (not the
 * OrderInterface) because setData() lives on AbstractModel/DataObject, not on the
 * interface — matching the house pattern in DeliveryPartnerResolverTest.
 */
class ShipmentResultPersisterTest extends TestCase
{
    private ShipmentResultPersister $persister;

    protected function setUp(): void
    {
        $this->persister = new ShipmentResultPersister();
    }

    /**
     * A ShadowFax result must stamp delivery_partner AND all three shadowfax_* columns.
     */
    public function testShadowfaxResultSetsAllShadowfaxColumnsAndPartner(): void
    {
        $result = new DeliveryShipmentResult(
            PartnerCode::SHADOWFAX,
            'SF-SHIP-9',
            'AWB-77',
            'ShadowFax Express',
            true
        );

        $order = $this->createMock(Order::class);

        $expected = [
            ['delivery_partner', PartnerCode::SHADOWFAX],
            ['shadowfax_shipment_id', 'SF-SHIP-9'],
            ['shadowfax_awb', 'AWB-77'],
            ['shadowfax_courier_name', 'ShadowFax Express'],
        ];

        $order->expects($this->exactly(count($expected)))
            ->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$expected) {
                $this->assertNotEmpty($expected, 'Unexpected extra setData call: ' . $key);
                [$expectedKey, $expectedValue] = array_shift($expected);
                $this->assertSame($expectedKey, $key);
                $this->assertSame($expectedValue, $value);
                return null;
            });

        $this->persister->apply($order, $result);

        $this->assertSame([], $expected, 'Not all expected setData calls were made');
    }

    /**
     * A Shiprocket result must stamp ONLY delivery_partner — never the shadowfax_* columns
     * (those belong to the existing Shiprocket persistence path; double-writing is a bug).
     */
    public function testShiprocketResultSetsOnlyDeliveryPartner(): void
    {
        $result = new DeliveryShipmentResult(
            PartnerCode::SHIPROCKET,
            'SR-SHIP-1',
            'AWB-SR',
            'Delhivery',
            true
        );

        $order = $this->createMock(Order::class);

        $order->expects($this->once())
            ->method('setData')
            ->with('delivery_partner', PartnerCode::SHIPROCKET);

        $this->persister->apply($order, $result);
    }

    /**
     * ShadowFax result with null identifiers still stamps the columns (as null) —
     * so a partial/pending record is represented explicitly rather than left stale.
     */
    public function testShadowfaxResultWithNullIdentifiersStillStampsColumns(): void
    {
        /** @var DeliveryShipmentResultInterface $result */
        $result = new DeliveryShipmentResult(PartnerCode::SHADOWFAX, null, null, null, false);

        $order = $this->createMock(Order::class);

        $expected = [
            ['delivery_partner', PartnerCode::SHADOWFAX],
            ['shadowfax_shipment_id', null],
            ['shadowfax_awb', null],
            ['shadowfax_courier_name', null],
        ];

        $order->expects($this->exactly(count($expected)))
            ->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$expected) {
                [$expectedKey, $expectedValue] = array_shift($expected);
                $this->assertSame($expectedKey, $key);
                $this->assertSame($expectedValue, $value);
                return null;
            });

        $this->persister->apply($order, $result);
    }
}
