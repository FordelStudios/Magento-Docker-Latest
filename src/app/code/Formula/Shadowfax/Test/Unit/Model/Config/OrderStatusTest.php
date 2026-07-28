<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Test\Unit\Model\Config;

use Formula\Shadowfax\Model\Config\OrderStatus;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    /**
     * @var array<string, array{label: string, state: string}>
     */
    private $statusMap;

    protected function setUp(): void
    {
        $this->statusMap = OrderStatus::getStatusMap();
    }

    public function testStatusMapIsNotEmpty(): void
    {
        $this->assertNotEmpty($this->statusMap);
    }

    public function testEveryEntryHasNonEmptyLabelAndValidState(): void
    {
        $validStates = [
            Order::STATE_PROCESSING,
            Order::STATE_COMPLETE,
            Order::STATE_CANCELED,
            Order::STATE_CLOSED,
        ];

        foreach ($this->statusMap as $code => $data) {
            $this->assertArrayHasKey('label', $data, "Status '$code' is missing a label");
            $this->assertArrayHasKey('state', $data, "Status '$code' is missing a state");
            $this->assertNotSame('', trim((string) $data['label']), "Status '$code' has an empty label");
            $this->assertContains(
                $data['state'],
                $validStates,
                "Status '$code' has an invalid state '{$data['state']}'"
            );
        }
    }

    public function testEveryStatusCodeIsPrefixedShadowfax(): void
    {
        foreach (array_keys($this->statusMap) as $code) {
            $this->assertStringStartsWith('shadowfax_', $code);
        }
    }

    public function testDeliveredMapsToCompleteState(): void
    {
        $this->assertArrayHasKey(OrderStatus::DELIVERED, $this->statusMap);
        $this->assertSame('shadowfax_delivered', OrderStatus::DELIVERED);
        $this->assertSame(Order::STATE_COMPLETE, $this->statusMap[OrderStatus::DELIVERED]['state']);
    }

    public function testCancelledMapsToCanceledState(): void
    {
        $this->assertArrayHasKey(OrderStatus::CANCELLED, $this->statusMap);
        $this->assertSame('shadowfax_cancelled', OrderStatus::CANCELLED);
        $this->assertSame(Order::STATE_CANCELED, $this->statusMap[OrderStatus::CANCELLED]['state']);
    }

    public function testRtoDeliveredMapsToClosedState(): void
    {
        $this->assertArrayHasKey(OrderStatus::RTO_DELIVERED, $this->statusMap);
        $this->assertSame(Order::STATE_CLOSED, $this->statusMap[OrderStatus::RTO_DELIVERED]['state']);
    }

    public function testAllExpectedStatusCodesArePresent(): void
    {
        $expectedCodes = [
            OrderStatus::SHIPMENT_CREATED,
            OrderStatus::PICKUP_SCHEDULED,
            OrderStatus::PICKED_UP,
            OrderStatus::IN_TRANSIT,
            OrderStatus::OUT_FOR_DELIVERY,
            OrderStatus::DELIVERED,
            OrderStatus::CANCELLED,
            OrderStatus::RTO_INITIATED,
            OrderStatus::RTO_DELIVERED,
        ];

        foreach ($expectedCodes as $code) {
            $this->assertArrayHasKey($code, $this->statusMap);
        }

        $this->assertCount(count($expectedCodes), $this->statusMap);
    }
}
