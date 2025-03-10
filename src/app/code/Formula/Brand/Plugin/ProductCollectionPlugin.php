<?php
namespace Formula\Brand\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductCollectionPlugin
{
    /**
     * Add brand attribute to product collections
     *
     * @param Collection $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterLoad(
        Collection $subject,
        Collection $result
    ) {
        if (!$subject->isAttributeSelected('brand') && !$subject->hasFlag('brand_added')) {
            $subject->addAttributeToSelect('brand');
            $subject->setFlag('brand_added', true);
        }
        
        return $result;
    }
}