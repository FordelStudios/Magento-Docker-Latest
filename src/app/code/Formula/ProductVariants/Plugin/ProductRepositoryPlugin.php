<?php

namespace Formula\ProductVariants\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
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
     * @param VariantHelper $variantHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        VariantHelper $variantHelper,
        LoggerInterface $logger
    ) {
        $this->variantHelper = $variantHelper;
        $this->logger = $logger;
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
     * Add variants data to product list and filter duplicates
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductSearchResultsInterface $searchResults
     * @return ProductSearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchResults
    ) {
        // Skip if we're currently fetching variants (prevent recursion)
        if (\Formula\ProductVariants\Helper\VariantHelper::isFetchingVariants()) {
            return $searchResults;
        }

        try {
            $products = $searchResults->getItems();

            if (empty($products)) {
                return $searchResults;
            }

            // Group products by base SKU
            $groups = $this->variantHelper->groupProductsByBaseSku($products);

            // Track which products to keep (first variant of each group)
            $productsToKeep = [];
            $baseSkusToProcess = [];

            foreach ($groups as $baseSku => $group) {
                // Get the first variant (smallest ML size)
                $firstVariant = $this->variantHelper->getFirstVariant($group);
                $productsToKeep[$firstVariant->getId()] = $firstVariant;
                $baseSkusToProcess[] = $baseSku;
            }

            // Fetch all variants for each base SKU and add to products
            foreach ($baseSkusToProcess as $baseSkuNormalized) {
                // The baseSku is already normalized (lowercase), so we need to use the original
                // Get the first product in this group to extract the original base SKU
                $firstProductInGroup = null;
                foreach ($productsToKeep as $product) {
                    $parsed = $this->variantHelper->parseProductSku($product->getSku());
                    if (strtolower(trim($parsed['base_sku'])) === $baseSkuNormalized) {
                        $firstProductInGroup = $product;
                        break;
                    }
                }

                if ($firstProductInGroup) {
                    // Use the first product's base SKU for fetching (maintains original case for query)
                    $parsed = $this->variantHelper->parseProductSku($firstProductInGroup->getSku());
                    $variantProducts = $this->variantHelper->getVariantsByBaseSku($parsed['base_sku']);
                    $this->addVariantsToProduct($firstProductInGroup, $variantProducts);
                }
            }

            // Update search results with filtered products
            $searchResults->setItems(array_values($productsToKeep));
            $searchResults->setTotalCount(count($productsToKeep));

        } catch (\Exception $e) {
            $this->logger->error('[ProductVariants] Error processing product variants: ' . $e->getMessage());
        }

        return $searchResults;
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
        // Parse product SKU to get base SKU
        $parsed = $this->variantHelper->parseProductSku($product->getSku());
        $baseSku = $parsed['base_sku'];

        // Fetch variants if not provided
        if ($variantProducts === null) {
            $variantProducts = $this->variantHelper->getVariantsByBaseSku($baseSku);
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
