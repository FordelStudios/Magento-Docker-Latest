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
        $ingredientId = $product->getCustomAttribute('ingredient') ? 
            $product->getCustomAttribute('ingredient')->getValue() : null;
            
        if ($ingredientId) {
            try {
                $ingredient = $this->ingredientRepository->getById($ingredientId);
                $extensionAttributes = $product->getExtensionAttributes();
                if ($extensionAttributes) {
                    $extensionAttributes->setIngredientName($ingredient->getName());
                    $product->setExtensionAttributes($extensionAttributes);
                }
            } catch (NoSuchEntityException $e) {
                // Ingredient not found
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