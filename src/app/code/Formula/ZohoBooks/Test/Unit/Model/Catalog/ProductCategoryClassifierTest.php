<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Test\Unit\Model\Catalog;

use Formula\ZohoBooks\Model\Catalog\ProductCategoryClassifier;
use Formula\ZohoBooks\Model\Gst\HsnResolver;
use PHPUnit\Framework\TestCase;

class ProductCategoryClassifierTest extends TestCase
{
    private ProductCategoryClassifier $classifier;

    protected function setUp(): void
    {
        $this->classifier = new ProductCategoryClassifier();
    }

    public function testClassifiesHairCategoryAsHaircare(): void
    {
        $this->assertSame(
            HsnResolver::BUCKET_HAIRCARE,
            $this->classifier->classify(['Hair Care', 'All Products'])
        );
    }

    /**
     * @dataProvider soapKeywordProvider
     */
    public function testClassifiesSoapKeywordsAsSoap(string $categoryName): void
    {
        $this->assertSame(
            HsnResolver::BUCKET_SOAP,
            $this->classifier->classify([$categoryName])
        );
    }

    public function soapKeywordProvider(): array
    {
        return [
            'soap' => ['Bath Soap'],
            'cleanser' => ['Face Cleanser'],
            'face wash' => ['Face Wash'],
            'body wash' => ['Body Wash'],
        ];
    }

    public function testClassifiesUnmatchedCategoryAsSkincareFallback(): void
    {
        $this->assertSame(
            HsnResolver::BUCKET_SKINCARE,
            $this->classifier->classify(['Best Sellers', 'New Arrivals'])
        );
    }

    public function testClassifiesEmptyCategoryListAsSkincareFallback(): void
    {
        $this->assertSame(HsnResolver::BUCKET_SKINCARE, $this->classifier->classify([]));
    }

    public function testMatchIsCaseInsensitive(): void
    {
        $this->assertSame(HsnResolver::BUCKET_HAIRCARE, $this->classifier->classify(['HAIR CARE']));
        $this->assertSame(HsnResolver::BUCKET_SOAP, $this->classifier->classify(['bath SOAP']));
    }

    public function testMatchIsSubstringNotExactEquality(): void
    {
        $this->assertSame(
            HsnResolver::BUCKET_HAIRCARE,
            $this->classifier->classify(['Premium Haircare Essentials'])
        );
    }

    public function testFindsMatchAnywhereInTheCategoryList(): void
    {
        $this->assertSame(
            HsnResolver::BUCKET_SOAP,
            $this->classifier->classify(['Best Sellers', 'New Arrivals', 'Cleanser'])
        );
    }

    public function testHairRuleTakesPrecedenceOverSoapRule(): void
    {
        // A category name matching both keyword groups must resolve via the
        // higher-priority rule (hair checked before soap).
        $this->assertSame(
            HsnResolver::BUCKET_HAIRCARE,
            $this->classifier->classify(['Hair Soap'])
        );
    }
}
