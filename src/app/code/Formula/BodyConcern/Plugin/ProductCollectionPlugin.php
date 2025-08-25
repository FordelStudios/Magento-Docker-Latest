<?php
namespace Formula\BodyConcern\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductCollectionPlugin
{
    /**
     * Add bodyconcern attribute to product collections
     *
     * @param Collection $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterLoad(
        Collection $subject,
        Collection $result
    ) {
        if (!$subject->isAttributeSelected('bodyconcern') && !$subject->hasFlag('bodyconcern_added')) {
            $subject->addAttributeToSelect('bodyconcern');
            $subject->setFlag('bodyconcern_added', true);
        }
        
        return $result;
    }
}