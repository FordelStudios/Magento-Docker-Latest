<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Test\Unit\Model;

use Formula\Shadowfax\Model\Config\OrderStatus;
use Formula\Shadowfax\Model\ShadowfaxWebhookHandler;
use Formula\Shadowfax\Model\StatusMapper;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the order-lookup + status-apply handler.
 *
 * Order lookup is mocked at the OrderRepositoryInterface/SearchCriteriaBuilder
 * boundary (no real DB, no real Magento collection) — that keeps this cheaply
 * unit-testable per the A4 task brief, at the cost of not proving the actual
 * SQL/index behaviour end-to-end. That surface (does shadowfax_awb really get
 * hit by the query as expected against a real DB) is deferred to a live/
 * integration check, not covered here.
 */
class ShadowfaxWebhookHandlerTest extends TestCase
{
    /**
     * @var MockObject|OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var MockObject|SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var MockObject|FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    private ShadowfaxWebhookHandler $handler;

    protected function setUp(): void
    {
        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilder->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder->method('setPageSize')->willReturnSelf();

        $this->filterBuilder = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterBuilder->method('setField')->willReturnSelf();
        $this->filterBuilder->method('setValue')->willReturnSelf();
        $this->filterBuilder->method('setConditionType')->willReturnSelf();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMockForAbstractClass();

        $this->handler = new ShadowfaxWebhookHandler(
            $this->orderRepository,
            $this->searchCriteriaBuilder,
            $this->filterBuilder,
            new StatusMapper(),
            $this->logger
        );
    }

    /**
     * @param Order[] $items
     * @return MockObject|OrderSearchResultInterface
     */
    private function searchResultReturning(array $items)
    {
        $searchResult = $this->getMockBuilder(OrderSearchResultInterface::class)
            ->getMockForAbstractClass();
        $searchResult->method('getItems')->willReturn($items);

        return $searchResult;
    }

    /**
     * @return MockObject|Order
     */
    private function buildOrderMock(string $incrementId)
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $order->method('getIncrementId')->willReturn($incrementId);

        return $order;
    }

    public function testMissingExternalStatusIsNoOpAndDoesNotQueryOrders(): void
    {
        $this->orderRepository->expects($this->never())->method('getList');
        $this->orderRepository->expects($this->never())->method('save');

        $result = $this->handler->handleTrackingUpdate('AWB1', null, null);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Missing status', $result['message']);
    }

    public function testOrderNotFoundByAwbOrShipmentIdIsNoOpAcksGracefully(): void
    {
        $this->filterBuilder->method('create')->willReturn(
            $this->getMockBuilder(Filter::class)->disableOriginalConstructor()->getMock()
        );
        $this->searchCriteriaBuilder->method('create')->willReturn(
            $this->getMockBuilder(SearchCriteria::class)->disableOriginalConstructor()->getMock()
        );

        // Both the AWB lookup and the shipment_id fallback come back empty.
        $this->orderRepository->method('getList')->willReturn($this->searchResultReturning([]));

        $this->orderRepository->expects($this->never())->method('save');

        $result = $this->handler->handleTrackingUpdate('AWB-UNKNOWN', 'SHIP-UNKNOWN', 'DELIVERED');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function testKnownStatusOnFoundOrderSetsStateStatusAndSaves(): void
    {
        $this->filterBuilder->method('create')->willReturn(
            $this->getMockBuilder(Filter::class)->disableOriginalConstructor()->getMock()
        );
        $this->searchCriteriaBuilder->method('create')->willReturn(
            $this->getMockBuilder(SearchCriteria::class)->disableOriginalConstructor()->getMock()
        );

        $order = $this->buildOrderMock('000000123');
        $this->orderRepository->method('getList')->willReturn($this->searchResultReturning([$order]));

        $order->expects($this->once())->method('setState')->with(Order::STATE_COMPLETE);
        $order->expects($this->once())->method('setStatus')->with(OrderStatus::DELIVERED);
        $order->expects($this->once())->method('addCommentToStatusHistory');

        $this->orderRepository->expects($this->once())->method('save')->with($order);

        $result = $this->handler->handleTrackingUpdate('AWB123456', null, 'DELIVERED');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString(OrderStatus::DELIVERED, $result['message']);
    }

    public function testUnknownExternalStatusOnFoundOrderIsNoOpDoesNotSave(): void
    {
        $this->filterBuilder->method('create')->willReturn(
            $this->getMockBuilder(Filter::class)->disableOriginalConstructor()->getMock()
        );
        $this->searchCriteriaBuilder->method('create')->willReturn(
            $this->getMockBuilder(SearchCriteria::class)->disableOriginalConstructor()->getMock()
        );

        $order = $this->buildOrderMock('000000456');
        $this->orderRepository->method('getList')->willReturn($this->searchResultReturning([$order]));

        $order->expects($this->never())->method('setState');
        $order->expects($this->never())->method('setStatus');
        $this->orderRepository->expects($this->never())->method('save');

        $result = $this->handler->handleTrackingUpdate('AWB123456', null, 'SOME_STATUS_NEVER_SEEN');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Unknown status', $result['message']);
    }

    public function testFallsBackToShipmentIdWhenAwbLookupMisses(): void
    {
        $this->filterBuilder->method('create')->willReturn(
            $this->getMockBuilder(Filter::class)->disableOriginalConstructor()->getMock()
        );
        $this->searchCriteriaBuilder->method('create')->willReturn(
            $this->getMockBuilder(SearchCriteria::class)->disableOriginalConstructor()->getMock()
        );

        $order = $this->buildOrderMock('000000789');

        // First call (AWB lookup) misses, second call (shipment_id fallback) hits.
        $this->orderRepository->expects($this->exactly(2))
            ->method('getList')
            ->willReturnOnConsecutiveCalls(
                $this->searchResultReturning([]),
                $this->searchResultReturning([$order])
            );

        $this->orderRepository->expects($this->once())->method('save')->with($order);

        $result = $this->handler->handleTrackingUpdate('AWB-NOT-ON-FILE', 'SF-SHIP-99', 'IN_TRANSIT');

        $this->assertTrue($result['success']);
    }

    public function testAwbIsPreferredOverShipmentIdWhenBothMatch(): void
    {
        $this->filterBuilder->method('create')->willReturn(
            $this->getMockBuilder(Filter::class)->disableOriginalConstructor()->getMock()
        );
        $this->searchCriteriaBuilder->method('create')->willReturn(
            $this->getMockBuilder(SearchCriteria::class)->disableOriginalConstructor()->getMock()
        );

        $order = $this->buildOrderMock('000000111');

        // AWB lookup hits immediately, so getList must only be called once —
        // the shipment_id fallback path must never run.
        $this->orderRepository->expects($this->once())
            ->method('getList')
            ->willReturn($this->searchResultReturning([$order]));

        $result = $this->handler->handleTrackingUpdate('AWB-ON-FILE', 'SF-SHIP-IGNORED', 'PICKED_UP');

        $this->assertTrue($result['success']);
    }
}
