<?php
namespace Formula\Ingredient\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Formula\Ingredient\Model\ResourceModel\Ingredient\CollectionFactory;

class Ingredient extends AbstractSource
{
    /**
     * @var CollectionFactory
     */
    protected $ingredientCollectionFactory;

    /**
     * @param CollectionFactory $ingredientCollectionFactory
     */
    public function __construct(
        CollectionFactory $ingredientCollectionFactory
    ) {
        $this->ingredientCollectionFactory = $ingredientCollectionFactory;
    }

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [];
            
            // Add empty option
            $this->_options[] = ['label' => __('-- Please Select --'), 'value' => ''];
            
            // Get all ingredients from your ingredient collection
            $ingredients = $this->ingredientCollectionFactory->create();
            
            foreach ($ingredients as $ingredient) {
                $this->_options[] = [
                    'label' => $ingredient->getName(),
                    'value' => $ingredient->getId()
                ];
            }
        }
        
        return $this->_options;
    }
}