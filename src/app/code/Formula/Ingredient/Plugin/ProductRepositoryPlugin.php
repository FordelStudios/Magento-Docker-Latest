<?php
namespace Formula\Ingredient\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Formula\Ingredient\Model\IngredientRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductRepositoryPlugin
{
    protected $ingredientRepository;
    
    public function __construct(
        IngredientRepository $ingredientRepository
    ) {
        $this->ingredientRepository = $ingredientRepository;
    }
    
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ) {
        $ingredientAttribute = $product->getCustomAttribute('ingredient');
        if ($ingredientAttribute) {
            $ingredientValue = $ingredientAttribute->getValue();
            
            if ($ingredientValue) {
                try {
                    $ingredientNames = [];
                    $ingredientIds = explode(',', $ingredientValue);
                    
                    foreach ($ingredientIds as $ingredientId) {
                        $ingredient = $this->ingredientRepository->getById($ingredientId);
                        $ingredientNames[] = $ingredient->getName();
                    }
                    
                    $extensionAttributes = $product->getExtensionAttributes();
                    if ($extensionAttributes) {
                        $extensionAttributes->setIngredientNames(implode(', ', $ingredientNames));
                        $product->setExtensionAttributes($extensionAttributes);
                    }
                } catch (NoSuchEntityException $e) {
                    // Ingredient not found
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