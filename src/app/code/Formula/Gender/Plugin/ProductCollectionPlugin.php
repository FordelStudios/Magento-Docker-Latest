<?php

namespace Formula\Gender\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductCollectionPlugin
{
    /**
     * Add gender attribute to product collection
     *
     * @param Collection $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterLoad(Collection $subject, Collection $result)
    {
        if (!$subject->isLoaded()) {
            $subject->addAttributeToSelect('gender');
        }
        return $result;
    }
}