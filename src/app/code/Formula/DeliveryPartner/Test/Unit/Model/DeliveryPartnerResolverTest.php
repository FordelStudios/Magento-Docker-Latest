<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Test\Unit\Model;

use Formula\DeliveryPartner\Api\DeliveryPartnerInterface;
use Formula\DeliveryPartner\Model\DeliveryPartnerResolver;
use Formula\DeliveryPartner\Model\PartnerCode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DeliveryPartnerResolver.
 *
 * Note: orders are mocked via the concrete \Magento\Sales\Model\Order class
 * rather than \Magento\Sales\Api\Data\OrderInterface — the interface itself
 * does not declare getData(), so an interface-only mock cannot stub it.
 * Order (via AbstractModel) provides getData() and still satisfies
 * OrderInterface, matching the house pattern used in
 * Shadowfax/Test/Unit/Model/Request/ManifestPayloadBuilderTest.php.
 */
class DeliveryPartnerResolverTest extends TestCase
{
    private const XML_PATH_ACTIVE_PARTNER = 'delivery_partner/general/active_partner';

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfig;

    /** @var DeliveryPartnerInterface|MockObject */
    private $shiprocketPartner;

    /** @var DeliveryPartnerInterface|MockObject */
    private $shadowfaxPartner;

    private array $partners;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);

        $this->shiprocketPartner = $this->createMock(DeliveryPartnerInterface::class);
        $this->shiprocketPartner->method('getCode')->willReturn(PartnerCode::SHIPROCKET);

        $this->shadowfaxPartner = $this->createMock(DeliveryPartnerInterface::class);
        $this->shadowfaxPartner->method('getCode')->willReturn(PartnerCode::SHADOWFAX);

        $this->partners = [
            PartnerCode::SHIPROCKET => $this->shiprocketPartner,
            PartnerCode::SHADOWFAX => $this->shadowfaxPartner,
        ];
    }

    private function createResolver(): DeliveryPartnerResolver
    {
        return new DeliveryPartnerResolver($this->partners, $this->scopeConfig);
    }

    /**
     * @param string|null $override
     * @return Order|MockObject
     */
    private function createOrder(?string $override)
    {
        $order = $this->createMock(Order::class);
        $order->method('getData')->with('delivery_partner')->willReturn($override);

        return $order;
    }

    public function testPerOrderOverridePresentAndValidReturnsThatPartner(): void
    {
        $this->scopeConfig->expects($this->never())->method('getValue');

        $order = $this->createOrder(PartnerCode::SHADOWFAX);
        $resolver = $this->createResolver();

        $this->assertSame($this->shadowfaxPartner, $resolver->resolve($order));
        $this->assertSame(PartnerCode::SHADOWFAX, $resolver->resolveCode($order));
    }

    public function testExplicitShiprocketOverrideResolvesToShiprocketPartner(): void
    {
        // Regression: an explicit per-order delivery_partner='shiprocket' must resolve via the
        // override path (the Shiprocket adapter IS registered in the DI partner map), not merely
        // fall through to the config default. This matters once orders are intent-stamped:
        // a 'shiprocket'-stamped order must resolve to Shiprocket even if the global default flips.
        $this->scopeConfig->expects($this->never())->method('getValue');

        $order = $this->createOrder(PartnerCode::SHIPROCKET);
        $resolver = $this->createResolver();

        $this->assertSame(PartnerCode::SHIPROCKET, $resolver->resolveCode($order));
        $this->assertSame($this->shiprocketPartner, $resolver->resolve($order));
    }

    public function testOverrideNullFallsBackToConfigDefault(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(self::XML_PATH_ACTIVE_PARTNER)
            ->willReturn(PartnerCode::SHIPROCKET);

        $order = $this->createOrder(null);
        $resolver = $this->createResolver();

        $this->assertSame($this->shiprocketPartner, $resolver->resolve($order));
        $this->assertSame(PartnerCode::SHIPROCKET, $resolver->resolveCode($order));
    }

    public function testOverrideEmptyStringFallsBackToConfigDefault(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(self::XML_PATH_ACTIVE_PARTNER)
            ->willReturn(PartnerCode::SHIPROCKET);

        $order = $this->createOrder('');
        $resolver = $this->createResolver();

        $this->assertSame($this->shiprocketPartner, $resolver->resolve($order));
    }

    public function testConfigDefaultShadowfaxReturnsShadowfaxPartner(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(self::XML_PATH_ACTIVE_PARTNER)
            ->willReturn(PartnerCode::SHADOWFAX);

        $order = $this->createOrder(null);
        $resolver = $this->createResolver();

        $this->assertSame($this->shadowfaxPartner, $resolver->resolve($order));
        $this->assertSame(PartnerCode::SHADOWFAX, $resolver->resolveCode($order));
    }

    public function testOverrideWithCodeNotInMapFallsBackToConfigDefault(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(self::XML_PATH_ACTIVE_PARTNER)
            ->willReturn(PartnerCode::SHIPROCKET);

        $order = $this->createOrder('some_unregistered_courier');
        $resolver = $this->createResolver();

        $this->assertSame($this->shiprocketPartner, $resolver->resolve($order));
    }

    public function testResolvedCodeNotInMapThrowsLocalizedException(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(self::XML_PATH_ACTIVE_PARTNER)
            ->willReturn('unknown_courier');

        $order = $this->createOrder(null);
        $resolver = $this->createResolver();

        $this->expectException(LocalizedException::class);
        $resolver->resolve($order);
    }

    public function testEmptyConfigValueFallsBackToShiprocketDefault(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(self::XML_PATH_ACTIVE_PARTNER)
            ->willReturn(null);

        $order = $this->createOrder(null);
        $resolver = $this->createResolver();

        $this->assertSame(PartnerCode::SHIPROCKET, $resolver->resolveCode($order));
        $this->assertSame($this->shiprocketPartner, $resolver->resolve($order));
    }
}
