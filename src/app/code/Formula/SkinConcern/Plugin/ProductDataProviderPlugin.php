<?php
namespace Formula\SkinConcern\Plugin;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;

class ProductDataProviderPlugin
{
    /**
     * Add skinconcern attribute to collection
     *
     * @param ProductDataProvider $subject
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $result
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function afterGetCollection(
        ProductDataProvider $subject,
        $result
    ) {
        if ($result) {
            $result->addAttributeToSelect('skinconcern');
        }
        
        return $result;
    }
}