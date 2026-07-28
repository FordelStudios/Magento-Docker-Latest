<?php
declare(strict_types=1);

namespace Formula\Shiprocket\Test\Unit\Observer;

use Formula\DeliveryPartner\Api\DeliveryPartnerInterface;
use Formula\DeliveryPartner\Model\Data\DeliveryShipmentResult;
use Formula\DeliveryPartner\Model\DeliveryPartnerResolver;
use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\DeliveryPartner\Model\ShipmentResultPersister;
use Formula\Shiprocket\Helper\Data as ShiprocketHelper;
use Formula\Shiprocket\Observer\CodOrderShiprocketSync;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the COD/wallet place_after observer's delivery-partner routing.
 *
 * Uses the REAL ShipmentResultPersister so the ShadowFax path assertions also prove the
 * persister writes the shadowfax_* columns end-to-end.
 */
class CodOrderShiprocketSyncTest extends TestCase
{
    /** @var ShiprocketShipmentService|MockObject */
    private $shiprocketService;

    /** @var ShiprocketHelper|MockObject */
    private $shiprocketHelper;

    /** @var OrderRepositoryInterface|MockObject */
    private $orderRepository;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var DeliveryPartnerResolver|MockObject */
    private $resolver;

    private CodOrderShiprocketSync $observer;

    protected function setUp(): void
    {
        $this->shiprocketService = $this->createMock(ShiprocketShipmentService::class);
        $this->shiprocketHelper = $this->createMock(ShiprocketHelper::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resolver = $this->createMock(DeliveryPartnerResolver::class);

        $this->shiprocketHelper->method('isEnabled')->willReturn(true);

        $this->observer = new CodOrderShiprocketSync(
            $this->shiprocketService,
            $this->shiprocketHelper,
            $this->orderRepository,
            $this->logger,
            $this->resolver,
            new ShipmentResultPersister()
        );
    }

    /**
     * @param array<string, mixed> $setDataSpy captured setData($k,$v) calls, by reference
     * @return Order|MockObject
     */
    private function makeCodOrder(array &$setDataSpy)
    {
        $payment = $this->createMock(Payment::class);
        $payment->method('getMethod')->willReturn('cashondelivery');

        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(101);
        $order->method('getIncrementId')->willReturn('100000101');
        $order->method('getPayment')->willReturn($payment);
        $order->method('getIsVirtual')->willReturn(false);
        // gating reads: no existing shiprocket data
        $order->method('getData')->willReturnCallback(
            fn ($key) => in_array($key, ['shiprocket_order_id', 'shiprocket_shipment_id'], true) ? null : null
        );
        $order->method('setData')->willReturnCallback(function ($key, $value) use (&$setDataSpy) {
            $setDataSpy[$key] = $value;
            return null;
        });

        return $order;
    }

    private function makeObserverEvent(Order $order): Observer
    {
        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->addMethods(['getOrder'])
            ->getMock();
        $event->method('getOrder')->willReturn($order);

        $observer = $this->createMock(Observer::class);
        $observer->method('getEvent')->willReturn($event);

        return $observer;
    }

    public function testShiprocketPathInvokesShiprocketServiceAndStampsPartner(): void
    {
        $setData = [];
        $order = $this->makeCodOrder($setData);

        $this->resolver->method('resolveCode')->with($order)->willReturn(PartnerCode::SHIPROCKET);

        // Shiprocket path MUST use the Shiprocket service, not the resolver abstraction.
        $this->resolver->expects($this->never())->method('resolve');
        $this->shiprocketService->expects($this->once())
            ->method('createShipment')
            ->with($order)
            ->willReturn([
                'success' => true,
                'shiprocket_order_id' => 'SR-1',
                'shipment_id' => 'SHIP-1',
                'awb_code' => 'AWB-1',
                'courier_name' => 'Delhivery',
            ]);
        $this->orderRepository->expects($this->once())->method('save')->with($order);

        $this->observer->execute($this->makeObserverEvent($order));

        $this->assertSame('SR-1', $setData['shiprocket_order_id']);
        $this->assertSame(PartnerCode::SHIPROCKET, $setData['delivery_partner']);
        $this->assertArrayNotHasKey('shadowfax_awb', $setData);
    }

    public function testNonShiprocketPathUsesResolverAbstractionAndPersistsShadowfaxFields(): void
    {
        $setData = [];
        $order = $this->makeCodOrder($setData);

        $this->resolver->method('resolveCode')->with($order)->willReturn(PartnerCode::SHADOWFAX);

        // ShadowFax path MUST NOT touch the Shiprocket service.
        $this->shiprocketService->expects($this->never())->method('createShipment');

        $partner = $this->createMock(DeliveryPartnerInterface::class);
        $partner->method('createShipment')->with($order)->willReturn(
            new DeliveryShipmentResult(PartnerCode::SHADOWFAX, 'SF-SHIP-1', 'SF-AWB-1', 'ShadowFax Air', true)
        );
        $this->resolver->expects($this->once())->method('resolve')->with($order)->willReturn($partner);
        $this->orderRepository->expects($this->once())->method('save')->with($order);

        $this->observer->execute($this->makeObserverEvent($order));

        $this->assertSame(PartnerCode::SHADOWFAX, $setData['delivery_partner']);
        $this->assertSame('SF-SHIP-1', $setData['shadowfax_shipment_id']);
        $this->assertSame('SF-AWB-1', $setData['shadowfax_awb']);
        $this->assertSame('ShadowFax Air', $setData['shadowfax_courier_name']);
        $this->assertArrayNotHasKey('shiprocket_order_id', $setData);
    }

    public function testNonShiprocketUnsuccessfulResultIsLeftForBackfillNoSave(): void
    {
        $setData = [];
        $order = $this->makeCodOrder($setData);

        $this->resolver->method('resolveCode')->with($order)->willReturn(PartnerCode::SHADOWFAX);

        $partner = $this->createMock(DeliveryPartnerInterface::class);
        // No AWB -> unsuccessful.
        $partner->method('createShipment')->with($order)->willReturn(
            new DeliveryShipmentResult(PartnerCode::SHADOWFAX, 'SF-SHIP-2', null, null, false)
        );
        $this->resolver->method('resolve')->with($order)->willReturn($partner);

        // Unsuccessful create must NOT persist anything and must NOT save.
        $this->orderRepository->expects($this->never())->method('save');

        $this->observer->execute($this->makeObserverEvent($order));

        $this->assertArrayNotHasKey('delivery_partner', $setData);
    }
}
