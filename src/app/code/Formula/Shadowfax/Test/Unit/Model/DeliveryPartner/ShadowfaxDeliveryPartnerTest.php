<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Test\Unit\Model\DeliveryPartner;

use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\Shadowfax\Model\Config\OrderStatus;
use Formula\Shadowfax\Model\Data\ShipmentResult;
use Formula\Shadowfax\Model\DeliveryPartner\ShadowfaxDeliveryPartner;
use Formula\Shadowfax\Service\ShadowfaxApiService;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShadowfaxDeliveryPartnerTest extends TestCase
{
    /** @var ShadowfaxApiService|MockObject */
    private $apiService;

    private ShadowfaxDeliveryPartner $adapter;

    protected function setUp(): void
    {
        $this->apiService = $this->createMock(ShadowfaxApiService::class);
        $this->adapter = new ShadowfaxDeliveryPartner($this->apiService);
    }

    public function testGetCodeReturnsShadowfax(): void
    {
        $this->assertSame(PartnerCode::SHADOWFAX, $this->adapter->getCode());
    }

    public function testCreateShipmentWrapsSuccessfulShipmentResultIntoDto(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $shipmentResult = new ShipmentResult('AWB123', 'SF-SHIP-1', 'ShadowFax Express', ['raw' => true]);

        $this->apiService->method('createShipment')->with($order)->willReturn($shipmentResult);

        // On success the adapter sets the ShadowFax initial status, mirroring Shiprocket's
        // 'shipment_created'. Status knowledge stays inside the Shadowfax module.
        $order->expects($this->once())->method('setStatus')->with(OrderStatus::SHIPMENT_CREATED);

        $result = $this->adapter->createShipment($order);

        $this->assertSame(PartnerCode::SHADOWFAX, $result->getPartnerCode());
        $this->assertSame('SF-SHIP-1', $result->getShipmentId());
        $this->assertSame('AWB123', $result->getAwb());
        $this->assertSame('ShadowFax Express', $result->getCourierName());
        $this->assertTrue($result->isSuccessful());
    }

    public function testCreateShipmentWithoutAwbIsNotSuccessful(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $shipmentResult = new ShipmentResult(null, 'SF-SHIP-2', null, []);

        $this->apiService->method('createShipment')->with($order)->willReturn($shipmentResult);

        // No AWB -> unsuccessful -> status must NOT be advanced.
        $order->expects($this->never())->method('setStatus');

        $result = $this->adapter->createShipment($order);

        $this->assertSame(PartnerCode::SHADOWFAX, $result->getPartnerCode());
        $this->assertSame('SF-SHIP-2', $result->getShipmentId());
        $this->assertNull($result->getAwb());
        $this->assertNull($result->getCourierName());
        $this->assertFalse($result->isSuccessful());
    }

    public function testCreateShipmentWithEmptyStringAwbIsNotSuccessful(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $shipmentResult = new ShipmentResult('', 'SF-SHIP-3', 'Courier X', []);

        $this->apiService->method('createShipment')->with($order)->willReturn($shipmentResult);

        $order->expects($this->never())->method('setStatus');

        $result = $this->adapter->createShipment($order);

        $this->assertFalse($result->isSuccessful());
    }
}
