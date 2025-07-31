<?php
namespace Formula\Brand\Plugin;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;

class ProductDataProviderPlugin
{
    /**
     * Add brand attribute to collection
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
            $result->addAttributeToSelect('brand');
        }
        
        return $result;
    }
}