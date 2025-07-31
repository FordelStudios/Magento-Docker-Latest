<?php
namespace Formula\SkinType\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Formula\SkinType\Model\SkinTypeRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductRepositoryPlugin
{
    protected $skintypeRepository;
    
    public function __construct(
        SkinTypeRepository $skintypeRepository
    ) {
        $this->skintypeRepository = $skintypeRepository;
    }
    
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $skintypeAttribute = $product->getCustomAttribute('skintype');
        if ($skintypeAttribute) {
            $skintypeValue = $skintypeAttribute->getValue();
            
            if ($skintypeValue) {
                try {
                    $skintypeNames = [];
                    $skintypeIds = explode(',', $skintypeValue);
                    
                    foreach ($skintypeIds as $skintypeId) {
                        $skintype = $this->skintypeRepository->getById($skintypeId);
                        $skintypeNames[] = $skintype->getName();
                    }
                    
                    $extensionAttributes = $product->getExtensionAttributes();
                    if ($extensionAttributes) {
                        $extensionAttributes->setSkinTypeNames(implode(', ', $skintypeNames));
                        $product->setExtensionAttributes($extensionAttributes);
                    }
                } catch (NoSuchEntityException $e) {
                    // SkinType not found
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