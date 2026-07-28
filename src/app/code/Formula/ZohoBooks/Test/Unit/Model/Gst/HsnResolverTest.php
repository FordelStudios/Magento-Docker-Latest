<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Test\Unit\Model\Gst;

use Formula\ZohoBooks\Model\Gst\HsnResolver;
use Formula\ZohoBooks\Model\Gst\HsnResult;
use PHPUnit\Framework\TestCase;

class HsnResolverTest extends TestCase
{
    private HsnResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new HsnResolver();
    }

    /**
     * @dataProvider bucketProvider
     */
    public function testResolveReturnsHsnAndRateForKnownBucket(string $categoryKey, string $expectedHsn, float $expectedRate): void
    {
        $result = $this->resolver->resolve($categoryKey);

        $this->assertInstanceOf(HsnResult::class, $result);
        $this->assertSame($expectedHsn, $result->hsn);
        $this->assertSame($expectedRate, $result->rate);
    }

    public function bucketProvider(): array
    {
        return [
            'skincare' => ['skincare', '3304', 18.0],
            'soap' => ['soap', '3401', 18.0],
            'haircare' => ['haircare', '3305', 18.0],
        ];
    }

    public function testResolveFallsBackToSkincareForUnknownBucket(): void
    {
        $result = $this->resolver->resolve('totally-unknown-bucket');

        $this->assertSame('3304', $result->hsn);
        $this->assertSame(18.0, $result->rate);
    }

    public function testResolveFallsBackToSkincareForEmptyBucket(): void
    {
        $result = $this->resolver->resolve('');

        $this->assertSame('3304', $result->hsn);
        $this->assertSame(18.0, $result->rate);
    }

    /**
     * @dataProvider caseInsensitiveProvider
     */
    public function testResolveIsCaseInsensitive(string $categoryKey, string $expectedHsn): void
    {
        $result = $this->resolver->resolve($categoryKey);

        $this->assertSame($expectedHsn, $result->hsn);
    }

    public function caseInsensitiveProvider(): array
    {
        return [
            'uppercase' => ['SKINCARE', '3304'],
            'mixed case' => ['Soap', '3401'],
            'padded whitespace' => [' haircare ', '3305'],
        ];
    }
}
