<?php
namespace Formula\SkinConcern\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Formula\SkinConcern\Model\SkinConcernRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductRepositoryPlugin
{
    protected $skinconcernRepository;
    
    public function __construct(
        SkinConcernRepository $skinconcernRepository
    ) {
        $this->skinconcernRepository = $skinconcernRepository;
    }
    
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $skinconcernAttribute = $product->getCustomAttribute('skinconcern');
        if ($skinconcernAttribute) {
            $skinconcernValue = $skinconcernAttribute->getValue();
            
            if ($skinconcernValue) {
                try {
                    $skinconcernNames = [];
                    $skinconcernIds = explode(',', $skinconcernValue);
                    
                    foreach ($skinconcernIds as $skinconcernId) {
                        $skinconcern = $this->skinconcernRepository->getById($skinconcernId);
                        $skinconcernNames[] = $skinconcern->getName();
                    }
                    
                    $extensionAttributes = $product->getExtensionAttributes();
                    if ($extensionAttributes) {
                        $extensionAttributes->setSkinConcernNames(implode(', ', $skinconcernNames));
                        $product->setExtensionAttributes($extensionAttributes);
                    }
                } catch (NoSuchEntityException $e) {
                    // SkinConcern not found
                }
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