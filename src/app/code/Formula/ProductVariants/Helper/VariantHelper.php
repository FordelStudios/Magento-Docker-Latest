<?php

namespace Formula\ProductVariants\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;

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
     */
    public function __construct(
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
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
     * Parse product SKU to extract base SKU and ML size
     *
     * @param string $sku
     * @return array ['base_sku' => string, 'ml_size' => string|null]
     */
    public function parseProductSku($sku)
    {
        // Match pattern: "Product SKU (125 ml)" or "Product SKU (125ml)"
        // Captures everything before the last (XXX ml) pattern
        if (preg_match('/^(.+?)\s*\((\d+)\s*ml\)\s*$/i', trim($sku), $matches)) {
            return [
                'base_sku' => trim($matches[1]),
                'ml_size' => $matches[2]
            ];
        }

        // No ML pattern found - treat as unique product
        return [
            'base_sku' => $sku,
            'ml_size' => null
        ];
    }

    /**
     * Get all variant products for a given base SKU
     * Uses ProductRepository to ensure stock data is properly loaded
     *
     * @param string $baseSku
     * @param int|null $storeId
     * @return ProductInterface[]
     */
    public function getVariantsByBaseSku($baseSku, $storeId = null)
    {
        $cacheKey = $baseSku . '_' . ($storeId ?? 'all');

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
            $skuFilter = $this->filterBuilder
                ->setField('sku')
                ->setValue($baseSku . ' (%ml)%')
                ->setConditionType('like')
                ->create();

            $statusFilter = $this->filterBuilder
                ->setField('status')
                ->setValue(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                ->setConditionType('eq')
                ->create();

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilters([$skuFilter])
                ->addFilters([$statusFilter])
                ->create();

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
     * @return array
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

            $variants[] = [
                'product_id' => (int)$product->getId(),
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'ml_size' => $parsed['ml_size'],
                'price' => (float)$product->getPrice(),
                'special_price' => $product->getSpecialPrice() ? (float)$product->getSpecialPrice() : null,
                'final_price' => $product->getSpecialPrice() ? (float)$product->getSpecialPrice() : (float)$product->getPrice(),
                'is_in_stock' => (bool)$isInStock,
                'salable_qty' => (float)$salableQty,
                'image' => $imageUrl
            ];
        }

        return $variants;
    }

    /**
     * Get the minimum price from a list of variants
     *
     * @param array $variants
     * @return float
     */
    public function getMinimumPrice(array $variants)
    {
        if (empty($variants)) {
            return 0.0;
        }

        $prices = array_map(function($variant) {
            return $variant['final_price'];
        }, $variants);

        return min($prices);
    }

    /**
     * Group products by base SKU
     *
     * @param ProductInterface[] $products
     * @return array [baseSku => [products]]
     */
    public function groupProductsByBaseSku(array $products)
    {
        $groups = [];

        foreach ($products as $product) {
            $parsed = $this->parseProductSku($product->getSku());
            // Normalize to lowercase for case-insensitive grouping
            $baseSkuNormalized = strtolower(trim($parsed['base_sku']));

            if (!isset($groups[$baseSkuNormalized])) {
                $groups[$baseSkuNormalized] = [];
            }

            $groups[$baseSkuNormalized][] = [
                'product' => $product,
                'ml_size' => $parsed['ml_size'] ? (int)$parsed['ml_size'] : PHP_INT_MAX
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
