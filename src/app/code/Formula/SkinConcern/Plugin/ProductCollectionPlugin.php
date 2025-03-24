<?php
namespace Formula\SkinConcern\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductCollectionPlugin
{
    /**
     * Add skinconcern attribute to product collections
     *
     * @param Collection $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterLoad(
        Collection $subject,
        Collection $result
    ) {
        if (!$subject->isAttributeSelected('skinconcern') && !$subject->hasFlag('skinconcern_added')) {
            $subject->addAttributeToSelect('skinconcern');
            $subject->setFlag('skinconcern_added', true);
        }
        
        return $result;
    }
}