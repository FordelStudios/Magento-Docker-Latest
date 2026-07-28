<?php
declare(strict_types=1);

namespace Formula\RazorpayApi\Test\Unit\Model;

use Formula\DeliveryPartner\Api\DeliveryPartnerInterface;
use Formula\DeliveryPartner\Model\Data\DeliveryShipmentResult;
use Formula\DeliveryPartner\Model\DeliveryPartnerResolver;
use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\DeliveryPartner\Model\ShipmentResultPersister;
use Formula\RazorpayApi\Api\Data\OrderResponseInterfaceFactory;
use Formula\RazorpayApi\Model\OrderManagement;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Magento\Framework\DB\Transaction;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the delivery-partner routing of the Razorpay (prepaid) shipment step ONLY.
 *
 * These exercise the shipment-creation seam in isolation via reflection. The surrounding
 * payment capture / invoicing / order-state logic is deliberately NOT touched by this change
 * and is out of scope here — end-to-end prepaid verification is deferred to a running stack
 * with real Razorpay + P1 ShadowFax credentials.
 *
 * The critical property proven: a ShadowFax shipment failure NEVER throws out of the shipment
 * step, so it can never unwind a payment/invoice that already succeeded.
 */
class OrderManagementShipmentRoutingTest extends TestCase
{
    /** @var ShiprocketShipmentService|MockObject */
    private $shiprocketService;

    /** @var OrderRepositoryInterface|MockObject */
    private $orderRepository;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var DeliveryPartnerResolver|MockObject */
    private $resolver;

    private OrderManagement $model;

    protected function setUp(): void
    {
        $this->shiprocketService = $this->createMock(ShiprocketShipmentService::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resolver = $this->createMock(DeliveryPartnerResolver::class);

        $this->model = new OrderManagement(
            $this->createMock(CartManagementInterface::class),
            $this->createMock(CartRepositoryInterface::class),
            $this->orderRepository,
            $this->createMock(BuilderInterface::class),
            $this->createMock(TransactionRepositoryInterface::class),
            $this->createMock(Transaction::class),
            $this->createMock(OrderResponseInterfaceFactory::class),
            $this->shiprocketService,
            $this->logger,
            $this->resolver,
            new ShipmentResultPersister()
        );
    }

    /**
     * @param array<string, mixed> $setDataSpy
     * @return Order|MockObject
     */
    private function makeOrder(array &$setDataSpy)
    {
        $order = $this->createMock(Order::class);
        $order->method('getIncrementId')->willReturn('100000202');
        $order->method('setData')->willReturnCallback(function ($key, $value) use (&$setDataSpy) {
            $setDataSpy[$key] = $value;
            return null;
        });

        return $order;
    }

    /**
     * @return mixed
     */
    private function invokePrivate(string $method, Order $order)
    {
        $ref = new \ReflectionMethod(OrderManagement::class, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->model, $order);
    }

    public function testPartnerDispatchRoutesShiprocketToShiprocketService(): void
    {
        $setData = [];
        $order = $this->makeOrder($setData);

        $this->resolver->method('resolveCode')->with($order)->willReturn(PartnerCode::SHIPROCKET);
        $this->resolver->expects($this->never())->method('resolve');
        $this->shiprocketService->expects($this->once())
            ->method('createShipment')
            ->with($order)
            ->willReturn([
                'success' => true,
                'shiprocket_order_id' => 'SR-9',
                'shipment_id' => 'SHIP-9',
                'awb_code' => 'AWB-9',
                'courier_name' => 'Delhivery',
            ]);

        $result = $this->invokePrivate('createPartnerShipment', $order);

        $this->assertTrue($result['success']);
        $this->assertSame(PartnerCode::SHIPROCKET, $setData['delivery_partner']);
    }

    public function testPartnerDispatchRoutesNonShiprocketThroughResolverAbstraction(): void
    {
        $setData = [];
        $order = $this->makeOrder($setData);

        $this->resolver->method('resolveCode')->with($order)->willReturn(PartnerCode::SHADOWFAX);
        $this->shiprocketService->expects($this->never())->method('createShipment');

        $partner = $this->createMock(DeliveryPartnerInterface::class);
        $partner->method('createShipment')->with($order)->willReturn(
            new DeliveryShipmentResult(PartnerCode::SHADOWFAX, 'SF-9', 'SF-AWB-9', 'ShadowFax Air', true)
        );
        $this->resolver->expects($this->once())->method('resolve')->with($order)->willReturn($partner);
        $this->orderRepository->expects($this->once())->method('save')->with($order);

        $result = $this->invokePrivate('createPartnerShipment', $order);

        $this->assertTrue($result['success']);
        $this->assertSame(PartnerCode::SHADOWFAX, $result['partner']);
        $this->assertSame(PartnerCode::SHADOWFAX, $setData['delivery_partner']);
        $this->assertSame('SF-AWB-9', $setData['shadowfax_awb']);
    }

    public function testNonShiprocketShipmentExceptionIsSwallowedNeverBreaksPayment(): void
    {
        $setData = [];
        $order = $this->makeOrder($setData);

        $this->resolver->method('resolveCode')->with($order)->willReturn(PartnerCode::SHADOWFAX);

        $partner = $this->createMock(DeliveryPartnerInterface::class);
        $partner->method('createShipment')->willThrowException(new \RuntimeException('ShadowFax API down'));
        $this->resolver->method('resolve')->with($order)->willReturn($partner);

        // Must never save on failure and must never rethrow.
        $this->orderRepository->expects($this->never())->method('save');

        $result = $this->invokePrivate('createPartnerShipment', $order);

        $this->assertFalse($result['success']);
        $this->assertArrayNotHasKey('delivery_partner', $setData);
    }

    public function testResolverFailureFallsBackToShiprocketDefault(): void
    {
        $setData = [];
        $order = $this->makeOrder($setData);

        // Resolver misconfiguration must not break the captured payment: fall back to Shiprocket.
        $this->resolver->method('resolveCode')
            ->with($order)
            ->willThrowException(new \RuntimeException('bad config'));
        $this->shiprocketService->expects($this->once())
            ->method('createShipment')
            ->with($order)
            ->willReturn([
                'success' => true,
                'shiprocket_order_id' => 'SR-10',
                'shipment_id' => 'SHIP-10',
                'awb_code' => 'AWB-10',
                'courier_name' => 'Delhivery',
            ]);

        $result = $this->invokePrivate('createPartnerShipment', $order);

        $this->assertTrue($result['success']);
        $this->assertSame(PartnerCode::SHIPROCKET, $setData['delivery_partner']);
    }
}
