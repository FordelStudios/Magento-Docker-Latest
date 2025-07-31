<?php
namespace Formula\Brand\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Formula\Brand\Model\BrandRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductRepositoryPlugin
{
    protected $brandRepository;
    
    public function __construct(
        BrandRepository $brandRepository
    ) {
        $this->brandRepository = $brandRepository;
    }
    
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $brandId = $product->getCustomAttribute('brand') ? 
            $product->getCustomAttribute('brand')->getValue() : null;
            
        if ($brandId) {
            try {
                $brand = $this->brandRepository->getById($brandId);
                $extensionAttributes = $product->getExtensionAttributes();
                if ($extensionAttributes) {
                    $extensionAttributes->setBrandName($brand->getName());
                    $product->setExtensionAttributes($extensionAttributes);
                }
            } catch (NoSuchEntityException $e) {
                // Brand not found
            }
        }
        
        return $product;
    }
    
    public function afterGetList(
        ProductRepositoryInterface $subject,
        \Magento\Catalog\Api\Data\ProductSearchResultsInterface $searchResults
    ) {
        $products = $searchResults->getItems();
        
        foreach ($products as $product) {
            $this->afterGet($subject, $product);
        }
        
        return $searchResults;
    }
}