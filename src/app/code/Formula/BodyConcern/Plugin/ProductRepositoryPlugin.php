<?php
namespace Formula\BodyConcern\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Formula\BodyConcern\Model\BodyConcernRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductRepositoryPlugin
{
    protected $bodyconcernRepository;
    
    public function __construct(
        BodyConcernRepository $bodyconcernRepository
    ) {
        $this->bodyconcernRepository = $bodyconcernRepository;
    }
    
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $bodyconcernAttribute = $product->getCustomAttribute('bodyconcern');
        if ($bodyconcernAttribute) {
            $bodyconcernValue = $bodyconcernAttribute->getValue();
            
            if ($bodyconcernValue) {
                try {
                    $bodyconcernNames = [];
                    $bodyconcernIds = explode(',', $bodyconcernValue);
                    
                    foreach ($bodyconcernIds as $bodyconcernId) {
                        $bodyconcern = $this->bodyconcernRepository->getById($bodyconcernId);
                        $bodyconcernNames[] = $bodyconcern->getName();
                    }
                    
                    $extensionAttributes = $product->getExtensionAttributes();
                    if ($extensionAttributes) {
                        $extensionAttributes->setBodyConcernNames(implode(', ', $bodyconcernNames));
                        $product->setExtensionAttributes($extensionAttributes);
                    }
                } catch (NoSuchEntityException $e) {
                    // BodyConcern not found
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