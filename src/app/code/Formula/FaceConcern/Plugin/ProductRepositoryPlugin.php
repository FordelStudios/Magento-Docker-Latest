<?php
namespace Formula\FaceConcern\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Formula\FaceConcern\Model\FaceConcernRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductRepositoryPlugin
{
    protected $faceconcernRepository;
    
    public function __construct(
        FaceConcernRepository $faceconcernRepository
    ) {
        $this->faceconcernRepository = $faceconcernRepository;
    }
    
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $faceconcernAttribute = $product->getCustomAttribute('faceconcern');
        if ($faceconcernAttribute) {
            $faceconcernValue = $faceconcernAttribute->getValue();
            
            if ($faceconcernValue) {
                try {
                    $faceconcernNames = [];
                    $faceconcernIds = explode(',', $faceconcernValue);
                    
                    foreach ($faceconcernIds as $faceconcernId) {
                        $faceconcern = $this->faceconcernRepository->getById($faceconcernId);
                        $faceconcernNames[] = $faceconcern->getName();
                    }
                    
                    $extensionAttributes = $product->getExtensionAttributes();
                    if ($extensionAttributes) {
                        $extensionAttributes->setFaceConcernNames(implode(', ', $faceconcernNames));
                        $product->setExtensionAttributes($extensionAttributes);
                    }
                } catch (NoSuchEntityException $e) {
                    // FaceConcern not found
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