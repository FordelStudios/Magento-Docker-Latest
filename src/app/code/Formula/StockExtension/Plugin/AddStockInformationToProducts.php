<?php
namespace Formula\StockExtension\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class AddStockInformationToProducts
{
    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var GetProductSalableQtyInterface
     */
    protected $getProductSalableQty;

    /**
     * @var StockResolverInterface
     */
    protected $stockResolver;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param StockRegistryInterface $stockRegistry
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        GetProductSalableQtyInterface $getProductSalableQty,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
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
        $this->addStockInformation($product);
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
            $this->addStockInformation($product);
        }
        
        return $searchResults;
    }

    /**
     * Add stock information to a product
     *
     * @param ProductInterface $product
     * @return void
     */
    private function addStockInformation(ProductInterface $product)
    {
        try {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            $extensionAttributes = $product->getExtensionAttributes();
            
           if ($extensionAttributes) {
                // Get salable quantity using MSI
                $salableQty = $this->getSalableQuantity($product->getSku());
                
                // Set is_in_stock (legacy - keep for backward compatibility)
                $extensionAttributes->setIsInStock($stockItem->getIsInStock());
                
                // Set salable_qty
                $extensionAttributes->setSalableQty($salableQty);
                
                // Set stock_status based on salable quantity (MSI logic)
                $stockStatus = ($salableQty > 0) ? 1 : 0;
                $extensionAttributes->setStockStatus($stockStatus);
                
                $product->setExtensionAttributes($extensionAttributes);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error adding stock information to product: ' . $product->getSku(),
                ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Get salable quantity for a product SKU
     *
     * @param string $sku
     * @return float
     */
    private function getSalableQuantity(string $sku): float
    {
        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
            $stock = $this->stockResolver->execute('website', $websiteCode);
            $stockId = $stock->getStockId();
            
            return $this->getProductSalableQty->execute($sku, $stockId);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning(
                'Could not get salable quantity for SKU: ' . $sku,
                ['exception' => $e->getMessage()]
            );
            return 0.0;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error getting salable quantity for SKU: ' . $sku,
                ['exception' => $e->getMessage()]
            );
            return 0.0;
        }
    }
}