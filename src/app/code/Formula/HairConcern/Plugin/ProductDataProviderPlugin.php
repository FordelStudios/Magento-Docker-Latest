<?php
namespace Formula\HairConcern\Plugin;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;

class ProductDataProviderPlugin
{
    /**
     * Add hairconcern attribute to collection
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
            $result->addAttributeToSelect('hairconcern');
        }
        
        return $result;
    }
}