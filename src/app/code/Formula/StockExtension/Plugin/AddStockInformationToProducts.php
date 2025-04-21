<?php
namespace Formula\StockExtension\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class AddStockInformationToProducts
{
    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Add stock information to product
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        $extensionAttributes = $product->getExtensionAttributes();
        
        if ($extensionAttributes) {
            $extensionAttributes->setIsInStock($stockItem->getIsInStock());
            $product->setExtensionAttributes($extensionAttributes);
        }
        
        return $product;
    }

    /**
     * Add stock information to product search results
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductSearchResultsInterface $searchResults
     * @return ProductSearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchResults
    ) {
        $products = $searchResults->getItems();
        
        foreach ($products as $product) {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            $extensionAttributes = $product->getExtensionAttributes();
            
            if ($extensionAttributes) {
                $extensionAttributes->setIsInStock($stockItem->getIsInStock());
                $product->setExtensionAttributes($extensionAttributes);
            }
        }
        
        return $searchResults;
    }
}