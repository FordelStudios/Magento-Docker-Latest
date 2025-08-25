<?php
namespace Formula\FaceConcern\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductCollectionPlugin
{
    /**
     * Add faceconcern attribute to product collections
     *
     * @param Collection $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterLoad(
        Collection $subject,
        Collection $result
    ) {
        if (!$subject->isAttributeSelected('faceconcern') && !$subject->hasFlag('faceconcern_added')) {
            $subject->addAttributeToSelect('faceconcern');
            $subject->setFlag('faceconcern_added', true);
        }
        
        return $result;
    }
}