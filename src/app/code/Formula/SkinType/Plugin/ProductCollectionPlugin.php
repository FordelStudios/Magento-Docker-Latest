<?php
namespace Formula\SkinType\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductCollectionPlugin
{
    /**
     * Add skintype attribute to product collections
     *
     * @param Collection $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterLoad(
        Collection $subject,
        Collection $result
    ) {
        if (!$subject->isAttributeSelected('skintype') && !$subject->hasFlag('skintype_added')) {
            $subject->addAttributeToSelect('skintype');
            $subject->setFlag('skintype_added', true);
        }
        
        return $result;
    }
}