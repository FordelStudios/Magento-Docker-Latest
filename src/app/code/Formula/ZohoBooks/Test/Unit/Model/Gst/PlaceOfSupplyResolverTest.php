<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Test\Unit\Model\Gst;

use Formula\ZohoBooks\Helper\Data as ZohoBooksHelper;
use Formula\ZohoBooks\Model\Gst\PlaceOfSupplyResolver;
use Formula\ZohoBooks\Model\Gst\PlaceOfSupplyResult;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaceOfSupplyResolverTest extends TestCase
{
    /** @var ZohoBooksHelper|MockObject */
    private $helper;

    private PlaceOfSupplyResolver $resolver;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(ZohoBooksHelper::class);
        $this->resolver = new PlaceOfSupplyResolver($this->helper);
    }

    public function testResolveReturnsIntrastateWhenStatesMatch(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('27');

        $result = $this->resolver->resolve('27');

        $this->assertInstanceOf(PlaceOfSupplyResult::class, $result);
        $this->assertSame(PlaceOfSupplyResolver::SUPPLY_TYPE_INTRASTATE, $result->supplyType);
        $this->assertSame('27', $result->placeOfSupply);
    }

    public function testResolveReturnsInterstateWhenStatesDiffer(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('27');

        $result = $this->resolver->resolve('06');

        $this->assertSame(PlaceOfSupplyResolver::SUPPLY_TYPE_INTERSTATE, $result->supplyType);
        $this->assertSame('06', $result->placeOfSupply);
    }

    public function testIntrastateSplitsTaxIntoEqualCgstAndSgst(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('27');

        $result = $this->resolver->resolve('27');
        $split = $result->splitTax(18.0);

        $this->assertSame(['cgst' => 9.0, 'sgst' => 9.0], $split);
    }

    public function testInterstateAssignsFullRateToIgst(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('27');

        $result = $this->resolver->resolve('06');
        $split = $result->splitTax(18.0);

        $this->assertSame(['igst' => 18.0], $split);
    }

    public function testIntrastateSplitsOddRateWithoutRounding(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('27');

        $result = $this->resolver->resolve('27');
        $split = $result->splitTax(5.0);

        $this->assertSame(['cgst' => 2.5, 'sgst' => 2.5], $split);
    }

    public function testInterstateAssignsFullOddRateToIgst(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('27');

        $result = $this->resolver->resolve('06');
        $split = $result->splitTax(5.0);

        $this->assertSame(['igst' => 5.0], $split);
    }

    public function testResolveZeroPadsSingleDigitCodesBeforeComparing(): void
    {
        // org "7" and customer "07" are both Delhi -> must classify as intrastate.
        $this->helper->method('getOrgGstStateCode')->willReturn('7');

        $result = $this->resolver->resolve('07');

        $this->assertSame(PlaceOfSupplyResolver::SUPPLY_TYPE_INTRASTATE, $result->supplyType);
        $this->assertSame('07', $result->placeOfSupply);
    }

    public function testResolveZeroPadsSingleDigitCustomerCode(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('27');

        $result = $this->resolver->resolve('6');

        $this->assertSame(PlaceOfSupplyResolver::SUPPLY_TYPE_INTERSTATE, $result->supplyType);
        $this->assertSame('06', $result->placeOfSupply);
    }

    /**
     * @dataProvider malformedCustomerCodeProvider
     */
    public function testResolveThrowsOnMalformedCustomerCode(string $customerStateCode): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('27');

        $this->expectException(LocalizedException::class);

        $this->resolver->resolve($customerStateCode);
    }

    public function malformedCustomerCodeProvider(): array
    {
        return [
            'three digits' => ['271'],
            'alpha' => ['AB'],
            'alphanumeric' => ['2A'],
        ];
    }

    /**
     * @dataProvider malformedOrgCodeProvider
     */
    public function testResolveThrowsOnMalformedOrgCode(string $orgStateCode): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn($orgStateCode);

        $this->expectException(LocalizedException::class);

        $this->resolver->resolve('27');
    }

    public function malformedOrgCodeProvider(): array
    {
        return [
            'three digits' => ['271'],
            'alpha' => ['AB'],
        ];
    }

    public function testResolveNormalizesWhitespaceBeforeComparing(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn(' 27 ');

        $result = $this->resolver->resolve(' 27 ');

        $this->assertSame(PlaceOfSupplyResolver::SUPPLY_TYPE_INTRASTATE, $result->supplyType);
        $this->assertSame('27', $result->placeOfSupply);
    }

    public function testResolveThrowsWhenCustomerStateCodeIsEmpty(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('27');

        $this->expectException(LocalizedException::class);

        $this->resolver->resolve('');
    }

    public function testResolveThrowsWhenCustomerStateCodeIsWhitespaceOnly(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('27');

        $this->expectException(LocalizedException::class);

        $this->resolver->resolve('   ');
    }

    public function testResolveThrowsWhenOrgStateCodeIsNotConfigured(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn(null);

        $this->expectException(LocalizedException::class);

        $this->resolver->resolve('27');
    }

    public function testResolveThrowsWhenOrgStateCodeIsEmptyString(): void
    {
        $this->helper->method('getOrgGstStateCode')->willReturn('');

        $this->expectException(LocalizedException::class);

        $this->resolver->resolve('27');
    }
}
