<?php
namespace Formula\HairConcern\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Formula\HairConcern\Model\HairConcernRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductRepositoryPlugin
{
    protected $hairconcernRepository;
    
    public function __construct(
        HairConcernRepository $hairconcernRepository
    ) {
        $this->hairconcernRepository = $hairconcernRepository;
    }
    
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $hairconcernAttribute = $product->getCustomAttribute('hairconcern');
        if ($hairconcernAttribute) {
            $hairconcernValue = $hairconcernAttribute->getValue();
            
            if ($hairconcernValue) {
                try {
                    $hairconcernNames = [];
                    $hairconcernIds = explode(',', $hairconcernValue);
                    
                    foreach ($hairconcernIds as $hairconcernId) {
                        $hairconcern = $this->hairconcernRepository->getById($hairconcernId);
                        $hairconcernNames[] = $hairconcern->getName();
                    }
                    
                    $extensionAttributes = $product->getExtensionAttributes();
                    if ($extensionAttributes) {
                        $extensionAttributes->setHairConcernNames(implode(', ', $hairconcernNames));
                        $product->setExtensionAttributes($extensionAttributes);
                    }
                } catch (NoSuchEntityException $e) {
                    // HairConcern not found
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