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
        $extensionAttributes = $product->getExtensionAttributes();
        if (!$extensionAttributes) {
            return $product;
        }

        // Handle key_ingredients attribute
        $this->processIngredientAttribute($product, 'key_ingredients', 'setKeyIngredientNames', $extensionAttributes);
        
        // Handle all_ingredients attribute
        $this->processIngredientAttribute($product, 'all_ingredients', 'setAllIngredientNames', $extensionAttributes);
        
        $product->setExtensionAttributes($extensionAttributes);
        return $product;
    }

    /**
     * Process ingredient attribute and set extension attribute
     *
     * @param ProductInterface $product
     * @param string $attributeCode
     * @param string $setterMethod
     * @param mixed $extensionAttributes
     * @return void
     */
    private function processIngredientAttribute($product, $attributeCode, $setterMethod, $extensionAttributes)
    {
        $ingredientAttribute = $product->getCustomAttribute($attributeCode);
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
                    
                    $extensionAttributes->$setterMethod(implode(', ', $ingredientNames));
                } catch (NoSuchEntityException $e) {
                    // Ingredient not found
                }
            }
        }
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