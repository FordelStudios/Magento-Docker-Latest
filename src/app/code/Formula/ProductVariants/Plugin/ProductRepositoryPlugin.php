<?php

namespace Formula\ProductVariants\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Formula\ProductVariants\Helper\VariantHelper;
use Psr\Log\LoggerInterface;

class ProductRepositoryPlugin
{
    /**
     * @var VariantHelper
     */
    private $variantHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProductSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @param VariantHelper $variantHelper
     * @param LoggerInterface $logger
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        VariantHelper $variantHelper,
        LoggerInterface $logger,
        ProductSearchResultsInterfaceFactory $searchResultsFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->variantHelper = $variantHelper;
        $this->logger = $logger;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * Add variants data to single product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        // Skip if we're currently fetching variants (prevent recursion)
        if (\Formula\ProductVariants\Helper\VariantHelper::isFetchingVariants()) {
            return $product;
        }

        try {
            $this->addVariantsToProduct($product);
        } catch (\Exception $e) {
            $this->logger->error('Error adding variants to product: ' . $e->getMessage());
        }

        return $product;
    }

    /**
     * Around plugin for getList to handle variant grouping with correct pagination
     *
     * This plugin intercepts the product list query and applies grouping BEFORE
     * pagination, ensuring that pageSize=20 returns exactly 20 unique product groups.
     *
     * @param ProductRepositoryInterface $subject
     * @param callable $proceed
     * @param SearchCriteriaInterface $searchCriteria
     * @return ProductSearchResultsInterface
     */
    public function aroundGetList(
        ProductRepositoryInterface $subject,
        callable $proceed,
        SearchCriteriaInterface $searchCriteria
    ) {
        // Skip if we're currently fetching variants (prevent recursion)
        if (\Formula\ProductVariants\Helper\VariantHelper::isFetchingVariants()) {
            return $proceed($searchCriteria);
        }

        try {
            // Extract pagination parameters from original request
            $pageSize = $searchCriteria->getPageSize() ?: 20;
            $currentPage = $searchCriteria->getCurrentPage() ?: 1;
            $targetOffset = ($currentPage - 1) * $pageSize;

            // Phase 1: Fetch ALL products to get accurate total_count of unique groups
            // We must collect all products to know the true count of unique groups
            $allGroupedProducts = [];
            $fetchPage = 1;
            $fetchSize = 100; // Fetch in batches of 100 for efficiency
            $maxIterations = 100; // Safety limit to prevent infinite loops

            while ($fetchPage <= $maxIterations) {
                // Create modified search criteria with batch pagination
                $fetchCriteria = $this->cloneSearchCriteriaWithPagination($searchCriteria, $fetchSize, $fetchPage);

                // Execute the original query with modified pagination
                $results = $proceed($fetchCriteria);
                $fetchedProducts = $results->getItems();

                // If no products returned, we've reached the end
                if (empty($fetchedProducts)) {
                    break;
                }

                // Group fetched products by base SKU
                $groups = $this->variantHelper->groupProductsByBaseSku($fetchedProducts);

                foreach ($groups as $group) {
                    $firstVariant = $this->variantHelper->getFirstVariant($group);
                    $groupKey = $this->variantHelper->getGroupKey($firstVariant);

                    // Only add if this group hasn't been seen before
                    if (!isset($allGroupedProducts[$groupKey])) {
                        $allGroupedProducts[$groupKey] = $firstVariant;
                    }
                }

                // If we fetched fewer products than requested, we've reached the end
                if (count($fetchedProducts) < $fetchSize) {
                    break;
                }

                $fetchPage++;
            }

            // Phase 2: Slice to get the requested page of grouped products
            $allGroupedArray = array_values($allGroupedProducts);
            $pageProducts = array_slice($allGroupedArray, $targetOffset, $pageSize);

            // Phase 3: Add variant data to each product in the current page
            foreach ($pageProducts as $product) {
                $this->addVariantsToProduct($product);
            }

            // Phase 4: Build the response with correct total_count
            $searchResults = $this->searchResultsFactory->create();
            $searchResults->setItems($pageProducts);
            // Total count is the number of unique groups (consistent across all pages)
            $searchResults->setTotalCount(count($allGroupedProducts));
            $searchResults->setSearchCriteria($searchCriteria);

            return $searchResults;

        } catch (\Exception $e) {
            $this->logger->error('[ProductVariants] Error in aroundGetList: ' . $e->getMessage());
            // Fallback to original behavior on error
            return $proceed($searchCriteria);
        }
    }

    /**
     * Clone search criteria with modified pagination parameters
     *
     * @param SearchCriteriaInterface $original
     * @param int $pageSize
     * @param int $currentPage
     * @return SearchCriteriaInterface
     */
    private function cloneSearchCriteriaWithPagination(
        SearchCriteriaInterface $original,
        int $pageSize,
        int $currentPage
    ): SearchCriteriaInterface {
        // Reset builder state
        $this->searchCriteriaBuilder->setPageSize($pageSize);
        $this->searchCriteriaBuilder->setCurrentPage($currentPage);

        // Copy filter groups from original criteria
        $filterGroups = $original->getFilterGroups();
        if ($filterGroups) {
            foreach ($filterGroups as $filterGroup) {
                $filters = [];
                foreach ($filterGroup->getFilters() as $filter) {
                    $newFilter = $this->filterBuilder
                        ->setField($filter->getField())
                        ->setValue($filter->getValue())
                        ->setConditionType($filter->getConditionType())
                        ->create();
                    $filters[] = $newFilter;
                }
                if (!empty($filters)) {
                    $this->searchCriteriaBuilder->addFilters($filters);
                }
            }
        }

        // Copy sort orders from original criteria
        $sortOrders = $original->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $newSortOrder = $this->sortOrderBuilder
                    ->setField($sortOrder->getField())
                    ->setDirection($sortOrder->getDirection())
                    ->create();
                $this->searchCriteriaBuilder->addSortOrder($newSortOrder);
            }
        }

        return $this->searchCriteriaBuilder->create();
    }

    /**
     * Add variants extension attribute to a product
     *
     * @param ProductInterface $product
     * @param array|null $variantProducts Pre-fetched variant products (optional)
     * @return void
     */
    private function addVariantsToProduct(ProductInterface $product, array $variantProducts = null)
    {
        // Parse product SKU to get base SKU and size info
        $parsed = $this->variantHelper->parseProductSku($product->getSku());
        $baseSku = $parsed['base_sku'];
        $hasSize = $parsed['ml_size'] !== null;

        // Fetch variants if not provided
        if ($variantProducts === null) {
            // Products WITHOUT size patterns should NOT search for variants
            // They are standalone and only show themselves
            if (!$hasSize) {
                $variantProducts = [$product];
            } else {
                // Products WITH size patterns fetch variants by base SKU + brand
                $brandId = $this->variantHelper->getProductBrandId($product);
                $variantProducts = $this->variantHelper->getVariantsByBaseSku($baseSku, $brandId);
            }
        }

        // If no variants found (shouldn't happen, but safeguard), create single variant
        if (empty($variantProducts)) {
            $variantProducts = [$product];
        }

        // Build variant data array
        $variants = $this->variantHelper->buildVariantData($variantProducts);

        // Get extension attributes
        $extensionAttributes = $product->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->createExtensionAttributes($product);
        }

        // Set variants data
        $extensionAttributes->setVariants($variants);
        $product->setExtensionAttributes($extensionAttributes);

        // Optional: Update product price to show minimum variant price
        // This ensures sorting by price works correctly
        if (count($variants) > 1) {
            $minPrice = $this->variantHelper->getMinimumPrice($variants);
            // Update product price for sorting purposes (shows lowest variant price)
            $product->setPrice($minPrice);
        }
    }

    /**
     * Create extension attributes object if it doesn't exist
     *
     * @param ProductInterface $product
     * @return \Magento\Catalog\Api\Data\ProductExtensionInterface
     */
    private function createExtensionAttributes(ProductInterface $product)
    {
        $extensionAttributesFactory = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory::class);
        return $extensionAttributesFactory->create();
    }
}
