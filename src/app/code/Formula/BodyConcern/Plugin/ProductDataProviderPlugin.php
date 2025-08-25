<?php
namespace Formula\BodyConcern\Plugin;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;

class ProductDataProviderPlugin
{
    /**
     * Add bodyconcern attribute to collection
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
            $result->addAttributeToSelect('bodyconcern');
        }
        
        return $result;
    }
}