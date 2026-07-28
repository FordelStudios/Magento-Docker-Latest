<?php
declare(strict_types=1);

namespace Formula\Shiprocket\Test\Unit\Cron;

use Formula\DeliveryPartner\Model\DeliveryPartnerResolver;
use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\Shiprocket\Cron\BackfillShiprocketShipments;
use Formula\Shiprocket\Helper\Data as ShiprocketHelper;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the Shiprocket backfill cron's per-order processing — focused on the new
 * delivery-partner guard that prevents Shiprocket-shipping an order routed to another partner.
 */
class BackfillShiprocketShipmentsTest extends TestCase
{
    /** @var ShiprocketShipmentService|MockObject */
    private $shiprocketService;

    /** @var OrderRepositoryInterface|MockObject */
    private $orderRepository;

    /** @var DeliveryPartnerResolver|MockObject */
    private $resolver;

    private BackfillShiprocketShipments $cron;

    protected function setUp(): void
    {
        $this->shiprocketService = $this->createMock(ShiprocketShipmentService::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->resolver = $this->createMock(DeliveryPartnerResolver::class);

        $this->cron = new BackfillShiprocketShipments(
            $this->shiprocketService,
            $this->createMock(ShiprocketHelper::class),
            $this->orderRepository,
            $this->createMock(OrderCollectionFactory::class),
            $this->createMock(LoggerInterface::class),
            $this->resolver
        );
    }

    /**
     * @param array<string, mixed> $setDataSpy
     * @return Order|MockObject
     */
    private function makeOrder(array &$setDataSpy)
    {
        $order = $this->createMock(Order::class);
        $order->method('getIncrementId')->willReturn('100000303');
        $order->method('getData')->willReturn(null); // no existing shiprocket ids
        $order->method('setData')->willReturnCallback(function ($key, $value) use (&$setDataSpy) {
            $setDataSpy[$key] = $value;
            return null;
        });

        return $order;
    }

    private function processOrder(int $orderId): void
    {
        $ref = new \ReflectionMethod(BackfillShiprocketShipments::class, 'processOrder');
        $ref->setAccessible(true);
        $ref->invoke($this->cron, $orderId);
    }

    public function testShadowfaxRoutedOrderIsSkippedNotShiprocketShipped(): void
    {
        $setData = [];
        $order = $this->makeOrder($setData);
        $this->orderRepository->method('get')->with(303)->willReturn($order);

        $this->resolver->method('resolveCode')->with($order)->willReturn(PartnerCode::SHADOWFAX);

        // The guard must stop a non-Shiprocket order from being Shiprocket-shipped.
        $this->shiprocketService->expects($this->never())->method('createShipment');
        $this->orderRepository->expects($this->never())->method('save');

        $this->processOrder(303);

        $this->assertSame([], $setData);
    }

    public function testShiprocketRoutedOrderIsShippedAndStampsPartner(): void
    {
        $setData = [];
        $order = $this->makeOrder($setData);
        $this->orderRepository->method('get')->with(303)->willReturn($order);

        $this->resolver->method('resolveCode')->with($order)->willReturn(PartnerCode::SHIPROCKET);
        $this->shiprocketService->expects($this->once())
            ->method('createShipment')
            ->with($order)
            ->willReturn([
                'success' => true,
                'shiprocket_order_id' => 'SR-3',
                'shipment_id' => 'SHIP-3',
                'awb_code' => 'AWB-3',
                'courier_name' => 'Delhivery',
            ]);
        $this->orderRepository->expects($this->once())->method('save')->with($order);

        $this->processOrder(303);

        $this->assertSame('SR-3', $setData['shiprocket_order_id']);
        $this->assertSame(PartnerCode::SHIPROCKET, $setData['delivery_partner']);
    }
}
