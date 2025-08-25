<?php
namespace Formula\HairConcern\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductCollectionPlugin
{
    /**
     * Add hairconcern attribute to product collections
     *
     * @param Collection $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterLoad(
        Collection $subject,
        Collection $result
    ) {
        if (!$subject->isAttributeSelected('hairconcern') && !$subject->hasFlag('hairconcern_added')) {
            $subject->addAttributeToSelect('hairconcern');
            $subject->setFlag('hairconcern_added', true);
        }
        
        return $result;
    }
}