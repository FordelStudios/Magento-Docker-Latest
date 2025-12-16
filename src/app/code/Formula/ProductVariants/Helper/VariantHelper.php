<?php

namespace Formula\ProductVariants\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Formula\ProductVariants\Api\Data\VariantInterfaceFactory;

class VariantHelper
{
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var VariantInterfaceFactory
     */
    private $variantFactory;

    /**
     * @var array
     */
    private $variantCache = [];

    /**
     * @var bool
     */
    private static $isFetchingVariants = false;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param VariantInterfaceFactory $variantFactory
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        VariantInterfaceFactory $variantFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->variantFactory = $variantFactory;
    }

    /**
     * Check if currently fetching variants (to prevent recursion)
     *
     * @return bool
     */
    public static function isFetchingVariants()
    {
        return self::$isFetchingVariants;
    }

    /**
     * Parse product SKU to extract base SKU and size
     * Supports multiple units: ml (milliliters), g (grams), kg (kilograms), oz (ounces)
     *
     * @param string $sku
     * @return array ['base_sku' => string, 'ml_size' => string|null, 'unit' => string|null]
     */
    public function parseProductSku($sku)
    {
        // Match pattern: "Product SKU (125 ml)" or "Product SKU (125ml)" or "Product SKU (100 g)" etc.
        // Supported units: ml, g, kg, oz, l (case-insensitive)
        // Captures everything before the last (XXX unit) pattern
        if (preg_match('/^(.+?)\s*\((\d+)\s*(ml|g|kg|oz|l)\)\s*$/i', trim($sku), $matches)) {
            return [
                'base_sku' => trim($matches[1]),
                'ml_size' => $matches[2],
                'unit' => strtolower($matches[3])
            ];
        }

        // No size pattern found - treat as unique product
        return [
            'base_sku' => $sku,
            'ml_size' => null,
            'unit' => null
        ];
    }

    /**
     * Get all variant products for a given base SKU and brand
     * Uses ProductRepository to ensure stock data is properly loaded
     *
     * @param string $baseSku
     * @param int|null $brandId Brand ID to filter by (required to prevent cross-brand merging)
     * @param int|null $storeId
     * @return ProductInterface[]
     */
    public function getVariantsByBaseSku($baseSku, $brandId = null, $storeId = null)
    {
        $cacheKey = $baseSku . '_brand_' . ($brandId ?? 'none') . '_' . ($storeId ?? 'all');

        if (isset($this->variantCache[$cacheKey])) {
            return $this->variantCache[$cacheKey];
        }

        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        // Set flag to prevent recursive plugin execution
        self::$isFetchingVariants = true;

        try {
            // Use ProductRepository with search criteria to get properly enriched products
            // Search for products with size suffix: "Base SKU (XXX ml)", "Base SKU (XXX g)", etc.
            // Only products with size patterns should be merged as variants
            $skuFilter = $this->filterBuilder
                ->setField('sku')
                ->setValue($baseSku . ' (%')
                ->setConditionType('like')
                ->create();

            $statusFilter = $this->filterBuilder
                ->setField('status')
                ->setValue(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                ->setConditionType('eq')
                ->create();

            $this->searchCriteriaBuilder
                ->addFilters([$skuFilter])
                ->addFilters([$statusFilter]);

            // Add brand filter if brand ID is provided
            if ($brandId !== null) {
                $brandFilter = $this->filterBuilder
                    ->setField('brand')
                    ->setValue($brandId)
                    ->setConditionType('eq')
                    ->create();
                $this->searchCriteriaBuilder->addFilters([$brandFilter]);
            }

            $searchCriteria = $this->searchCriteriaBuilder->create();

            $searchResults = $this->productRepository->getList($searchCriteria);
            $products = $searchResults->getItems();
        } catch (\Exception $e) {
            // Reset flag and return empty array if search fails
            self::$isFetchingVariants = false;
            return [];
        } finally {
            // Always reset the flag
            self::$isFetchingVariants = false;
        }

        // Filter and sort by ML size
        $variants = [];
        foreach ($products as $product) {
            $parsed = $this->parseProductSku($product->getSku());

            // Double-check base SKU matches (case-insensitive)
            if (strcasecmp(trim($parsed['base_sku']), trim($baseSku)) === 0) {
                // Also verify brand matches if specified
                if ($brandId !== null) {
                    $productBrandId = $this->getProductBrandId($product);
                    if ($productBrandId !== $brandId) {
                        continue;
                    }
                }
                $variants[] = [
                    'product' => $product,
                    'ml_size' => $parsed['ml_size'] ? (int)$parsed['ml_size'] : PHP_INT_MAX
                ];
            }
        }

        // Sort by ML size (smallest first)
        usort($variants, function($a, $b) {
            return $a['ml_size'] <=> $b['ml_size'];
        });

        $sortedProducts = array_map(function($item) {
            return $item['product'];
        }, $variants);

        $this->variantCache[$cacheKey] = $sortedProducts;

        return $sortedProducts;
    }

    /**
     * Build variant data array for API response
     *
     * @param ProductInterface[] $variantProducts
     * @return \Formula\ProductVariants\Api\Data\VariantInterface[]
     */
    public function buildVariantData(array $variantProducts)
    {
        $variants = [];

        foreach ($variantProducts as $product) {
            $parsed = $this->parseProductSku($product->getSku());

            // Get extension attributes for stock data (added by StockExtension module)
            $extensionAttributes = $product->getExtensionAttributes();
            $isInStock = $extensionAttributes && $extensionAttributes->getIsInStock() !== null
                ? $extensionAttributes->getIsInStock()
                : true;
            $salableQty = $extensionAttributes && $extensionAttributes->getSalableQty() !== null
                ? $extensionAttributes->getSalableQty()
                : 0;

            // Get image URL
            $imageUrl = null;
            if ($product->getImage() && $product->getImage() !== 'no_selection') {
                try {
                    $imageUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                        . 'catalog/product' . $product->getImage();
                } catch (\Exception $e) {
                    $imageUrl = null;
                }
            }

            // Create variant DTO object
            $variant = $this->variantFactory->create();
            $variant->setProductId((int)$product->getId());
            $variant->setSku($product->getSku());
            $variant->setName($product->getName());
            $variant->setSize($parsed['ml_size']);
            $variant->setUnit($parsed['unit'] ?? null);
            $variant->setPrice((float)$product->getPrice());
            $variant->setSpecialPrice($product->getSpecialPrice() ? (float)$product->getSpecialPrice() : null);
            $variant->setFinalPrice($product->getSpecialPrice() ? (float)$product->getSpecialPrice() : (float)$product->getPrice());
            $variant->setIsInStock((bool)$isInStock);
            $variant->setSalableQty((float)$salableQty);
            $variant->setImage($imageUrl);

            $variants[] = $variant;
        }

        return $variants;
    }

    /**
     * Get the minimum price from a list of variants
     *
     * @param \Formula\ProductVariants\Api\Data\VariantInterface[] $variants
     * @return float
     */
    public function getMinimumPrice(array $variants)
    {
        if (empty($variants)) {
            return 0.0;
        }

        $prices = array_map(function($variant) {
            return $variant->getFinalPrice();
        }, $variants);

        return min($prices);
    }

    /**
     * Get brand ID from product
     *
     * @param ProductInterface $product
     * @return int|null
     */
    public function getProductBrandId(ProductInterface $product)
    {
        $brandId = $product->getCustomAttribute('brand');
        if ($brandId) {
            return (int)$brandId->getValue();
        }
        return null;
    }

    /**
     * Group products by base SKU and brand
     * Products are only grouped together if they:
     * 1. Have a size pattern (e.g., "Product (100 ml)" or "Product (50 g)")
     * 2. Share the same base SKU AND brand
     * Products WITHOUT size patterns remain as standalone items (not grouped)
     *
     * @param ProductInterface[] $products
     * @return array [baseSku_brandId => [products]]
     */
    public function groupProductsByBaseSku(array $products)
    {
        $groups = [];

        foreach ($products as $product) {
            $parsed = $this->parseProductSku($product->getSku());
            $brandId = $this->getProductBrandId($product);

            // Products WITHOUT size patterns should NOT be grouped
            // Each one gets its own unique group key based on full SKU
            if ($parsed['ml_size'] === null) {
                // Use full SKU as group key to keep it standalone
                $groupKey = 'standalone_' . strtolower(trim($product->getSku())) . '_brand_' . ($brandId ?? 'none');
            } else {
                // Products WITH size patterns are grouped by base SKU + brand
                $baseSkuNormalized = strtolower(trim($parsed['base_sku']));
                $groupKey = $baseSkuNormalized . '_brand_' . ($brandId ?? 'none');
            }

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [];
            }

            $groups[$groupKey][] = [
                'product' => $product,
                'ml_size' => $parsed['ml_size'] ? (int)$parsed['ml_size'] : PHP_INT_MAX,
                'brand_id' => $brandId
            ];
        }

        return $groups;
    }

    /**
     * Get the first variant (smallest ML) from a group
     *
     * @param array $group [['product' => ProductInterface, 'ml_size' => int], ...]
     * @return ProductInterface
     */
    public function getFirstVariant(array $group)
    {
        // Sort by ML size (smallest first)
        usort($group, function($a, $b) {
            return $a['ml_size'] <=> $b['ml_size'];
        });

        return $group[0]['product'];
    }

    /**
     * Clear variant cache
     */
    public function clearCache()
    {
        $this->variantCache = [];
    }
}
