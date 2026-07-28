<?php
declare(strict_types=1);

namespace Formula\Shiprocket\Test\Unit\Model\DeliveryPartner;

use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\Shiprocket\Model\DeliveryPartner\ShiprocketDeliveryPartner;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShiprocketDeliveryPartnerTest extends TestCase
{
    /** @var ShiprocketShipmentService|MockObject */
    private $shipmentService;

    private ShiprocketDeliveryPartner $adapter;

    protected function setUp(): void
    {
        $this->shipmentService = $this->createMock(ShiprocketShipmentService::class);
        $this->adapter = new ShiprocketDeliveryPartner($this->shipmentService);
    }

    public function testGetCodeReturnsShiprocket(): void
    {
        $this->assertSame(PartnerCode::SHIPROCKET, $this->adapter->getCode());
    }

    public function testCreateShipmentWrapsSuccessfulArrayResultIntoDto(): void
    {
        $order = $this->createMock(OrderInterface::class);

        $this->shipmentService->method('createShipment')->with($order)->willReturn([
            'success' => true,
            'shiprocket_order_id' => 12345,
            'shipment_id' => 998877,
            'awb_code' => 'AWB998877',
            'courier_name' => 'Delhivery',
            'message' => 'Shipment created successfully',
        ]);

        $result = $this->adapter->createShipment($order);

        $this->assertSame(PartnerCode::SHIPROCKET, $result->getPartnerCode());
        $this->assertSame('998877', $result->getShipmentId());
        $this->assertSame('AWB998877', $result->getAwb());
        $this->assertSame('Delhivery', $result->getCourierName());
        $this->assertTrue($result->isSuccessful());
    }

    public function testCreateShipmentWithoutAwbIsNotSuccessful(): void
    {
        $order = $this->createMock(OrderInterface::class);

        $this->shipmentService->method('createShipment')->with($order)->willReturn([
            'success' => true,
            'shiprocket_order_id' => 12345,
            'shipment_id' => 998877,
            'awb_code' => null,
            'courier_name' => null,
            'message' => 'Shipment created successfully',
        ]);

        $result = $this->adapter->createShipment($order);

        $this->assertSame(PartnerCode::SHIPROCKET, $result->getPartnerCode());
        $this->assertSame('998877', $result->getShipmentId());
        $this->assertNull($result->getAwb());
        $this->assertNull($result->getCourierName());
        $this->assertFalse($result->isSuccessful());
    }

    public function testCreateShipmentWithMissingKeysDoesNotErrorAndIsNotSuccessful(): void
    {
        $order = $this->createMock(OrderInterface::class);

        $this->shipmentService->method('createShipment')->with($order)->willReturn([
            'success' => true,
            'message' => 'Shipment created successfully',
        ]);

        $result = $this->adapter->createShipment($order);

        $this->assertNull($result->getShipmentId());
        $this->assertNull($result->getAwb());
        $this->assertNull($result->getCourierName());
        $this->assertFalse($result->isSuccessful());
    }
}
