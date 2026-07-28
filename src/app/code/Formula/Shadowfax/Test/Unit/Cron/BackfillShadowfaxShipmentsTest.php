<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Test\Unit\Cron;

use Formula\DeliveryPartner\Model\Data\DeliveryShipmentResult;
use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\DeliveryPartner\Model\ShipmentResultPersister;
use Formula\Shadowfax\Cron\BackfillShadowfaxShipments;
use Formula\Shadowfax\Helper\Data as ShadowfaxHelper;
use Formula\Shadowfax\Model\DeliveryPartner\ShadowfaxDeliveryPartner;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the ShadowFax backfill cron's per-order processing.
 *
 * P1-GATED: exercises only the branching/persistence seam with a mocked adapter; the live
 * ShadowFax API call is unverified until real credentials land.
 */
class BackfillShadowfaxShipmentsTest extends TestCase
{
    /** @var ShadowfaxDeliveryPartner|MockObject */
    private $adapter;

    /** @var OrderRepositoryInterface|MockObject */
    private $orderRepository;

    private BackfillShadowfaxShipments $cron;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(ShadowfaxDeliveryPartner::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);

        $this->cron = new BackfillShadowfaxShipments(
            $this->createMock(ShadowfaxHelper::class),
            $this->adapter,
            new ShipmentResultPersister(),
            $this->orderRepository,
            $this->createMock(OrderCollectionFactory::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @param array<string, mixed> $setDataSpy
     * @return Order|MockObject
     */
    private function makeOrder(array &$setDataSpy)
    {
        $order = $this->createMock(Order::class);
        $order->method('getIncrementId')->willReturn('100000404');
        $order->method('getData')->willReturn(null); // no existing shadowfax_awb
        $order->method('setData')->willReturnCallback(function ($key, $value) use (&$setDataSpy) {
            $setDataSpy[$key] = $value;
            return null;
        });

        return $order;
    }

    private function processOrder(int $orderId): void
    {
        $ref = new \ReflectionMethod(BackfillShadowfaxShipments::class, 'processOrder');
        $ref->setAccessible(true);
        $ref->invoke($this->cron, $orderId);
    }

    public function testSuccessfulShipmentPersistsShadowfaxFieldsAndSaves(): void
    {
        $setData = [];
        $order = $this->makeOrder($setData);
        $this->orderRepository->method('get')->with(404)->willReturn($order);

        $this->adapter->method('createShipment')->with($order)->willReturn(
            new DeliveryShipmentResult(PartnerCode::SHADOWFAX, 'SF-SHIP-4', 'SF-AWB-4', 'ShadowFax Air', true)
        );
        $this->orderRepository->expects($this->once())->method('save')->with($order);

        $this->processOrder(404);

        $this->assertSame(PartnerCode::SHADOWFAX, $setData['delivery_partner']);
        $this->assertSame('SF-AWB-4', $setData['shadowfax_awb']);
        $this->assertSame('SF-SHIP-4', $setData['shadowfax_shipment_id']);
        $this->assertSame('ShadowFax Air', $setData['shadowfax_courier_name']);
    }

    public function testUnsuccessfulShipmentDoesNotPersistOrSave(): void
    {
        $setData = [];
        $order = $this->makeOrder($setData);
        $this->orderRepository->method('get')->with(404)->willReturn($order);

        $this->adapter->method('createShipment')->with($order)->willReturn(
            new DeliveryShipmentResult(PartnerCode::SHADOWFAX, 'SF-SHIP-5', null, null, false)
        );
        $this->orderRepository->expects($this->never())->method('save');

        $this->processOrder(404);

        $this->assertSame([], $setData);
    }
}
