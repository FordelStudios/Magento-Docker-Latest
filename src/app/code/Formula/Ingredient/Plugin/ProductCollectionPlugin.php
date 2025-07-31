<?php
namespace Formula\Ingredient\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductCollectionPlugin
{
    /**
     * Add ingredient attribute to product collections
     *
     * @param Collection $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterLoad(
        Collection $subject,
        Collection $result
    ) {
        if (!$subject->isAttributeSelected('ingredient') && !$subject->hasFlag('ingredient_added')) {
            $subject->addAttributeToSelect('ingredient');
            $subject->setFlag('ingredient_added', true);
        }
        
        return $result;
    }
}