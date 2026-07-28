<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Test\Unit\Model;

use Formula\Shadowfax\Model\Config\OrderStatus;
use Formula\Shadowfax\Model\StatusMapper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the pure external -> internal status mapping.
 *
 * NOTE: these tests verify the mapping LOGIC against our ASSUMED ShadowFax
 * status vocabulary (see StatusMapper class docblock). They do not, and
 * cannot, verify the mapping against statuses ShadowFax actually sends on
 * their real tracking webhook — we do not have credentials/docs yet.
 */
class StatusMapperTest extends TestCase
{
    private StatusMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new StatusMapper();
    }

    /**
     * @dataProvider knownStatusProvider
     */
    public function testKnownExternalStatusMapsToExpectedInternalStatus(
        string $externalStatus,
        string $expectedInternalStatus
    ): void {
        $this->assertSame($expectedInternalStatus, $this->mapper->mapExternalStatus($externalStatus));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function knownStatusProvider(): array
    {
        return [
            'shipment created' => ['SHIPMENT_CREATED', OrderStatus::SHIPMENT_CREATED],
            'pickup scheduled' => ['PICKUP_SCHEDULED', OrderStatus::PICKUP_SCHEDULED],
            'picked up' => ['PICKED_UP', OrderStatus::PICKED_UP],
            'picked (short form)' => ['PICKED', OrderStatus::PICKED_UP],
            'in transit' => ['IN_TRANSIT', OrderStatus::IN_TRANSIT],
            'out for delivery' => ['OUT_FOR_DELIVERY', OrderStatus::OUT_FOR_DELIVERY],
            'delivered' => ['DELIVERED', OrderStatus::DELIVERED],
            'cancelled (double-l)' => ['CANCELLED', OrderStatus::CANCELLED],
            'canceled (single-l)' => ['CANCELED', OrderStatus::CANCELLED],
            'rto' => ['RTO', OrderStatus::RTO_INITIATED],
            'rto initiated' => ['RTO_INITIATED', OrderStatus::RTO_INITIATED],
            'rto delivered' => ['RTO_DELIVERED', OrderStatus::RTO_DELIVERED],
        ];
    }

    public function testMatchIsCaseInsensitive(): void
    {
        $this->assertSame(OrderStatus::DELIVERED, $this->mapper->mapExternalStatus('delivered'));
        $this->assertSame(OrderStatus::DELIVERED, $this->mapper->mapExternalStatus('Delivered'));
        $this->assertSame(OrderStatus::DELIVERED, $this->mapper->mapExternalStatus('DeLiVeReD'));
    }

    public function testSurroundingWhitespaceIsTrimmed(): void
    {
        $this->assertSame(OrderStatus::IN_TRANSIT, $this->mapper->mapExternalStatus('  IN_TRANSIT  '));
    }

    public function testUnknownExternalStatusReturnsNull(): void
    {
        $this->assertNull($this->mapper->mapExternalStatus('SOME_STATUS_SHADOWFAX_HAS_NEVER_SENT_US'));
    }

    public function testEmptyStringReturnsNull(): void
    {
        $this->assertNull($this->mapper->mapExternalStatus(''));
    }

    public function testWhitespaceOnlyStringReturnsNull(): void
    {
        $this->assertNull($this->mapper->mapExternalStatus('   '));
    }
}
