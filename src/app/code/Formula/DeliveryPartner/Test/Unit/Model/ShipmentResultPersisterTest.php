<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Test\Unit\Model;

use Formula\DeliveryPartner\Model\Data\DeliveryShipmentResult;
use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\DeliveryPartner\Model\ShipmentResultPersister;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ShipmentResultPersister.
 *
 * Since intent-stamping moved delivery_partner ownership to the call sites (stamped early,
 * riding an existing save), this persister now owns ONLY the shadowfax_* identifier columns.
 *
 * The order is mocked via the concrete \Magento\Sales\Model\Order class (not the
 * OrderInterface) because setData() lives on AbstractModel/DataObject, not on the interface.
 */
class ShipmentResultPersisterTest extends TestCase
{
    private ShipmentResultPersister $persister;

    protected function setUp(): void
    {
        $this->persister = new ShipmentResultPersister();
    }

    /**
     * A ShadowFax result stamps the three shadowfax_* columns — and must NOT write
     * delivery_partner (that is owned by the attempt-time intent stamp at the call site).
     */
    public function testShadowfaxResultSetsShadowfaxColumnsOnlyNotDeliveryPartner(): void
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
                $this->assertNotSame('delivery_partner', $key, 'persister must not own delivery_partner');
                return null;
            });

        $this->persister->apply($order, $result);

        $this->assertSame([], $expected, 'Not all expected setData calls were made');
    }

    /**
     * A Shiprocket result writes NOTHING — the existing Shiprocket code persists its own
     * shiprocket_* columns and delivery_partner is stamped at attempt time elsewhere.
     */
    public function testShiprocketResultWritesNothing(): void
    {
        $result = new DeliveryShipmentResult(
            PartnerCode::SHIPROCKET,
            'SR-SHIP-1',
            'AWB-SR',
            'Delhivery',
            true
        );

        $order = $this->createMock(Order::class);
        $order->expects($this->never())->method('setData');

        $this->persister->apply($order, $result);
    }

    /**
     * ShadowFax result with null identifiers still stamps the columns (as null) —
     * representing a partial/pending record explicitly rather than leaving stale data.
     */
    public function testShadowfaxResultWithNullIdentifiersStillStampsColumns(): void
    {
        $result = new DeliveryShipmentResult(PartnerCode::SHADOWFAX, null, null, null, false);

        $order = $this->createMock(Order::class);

        $expected = [
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
