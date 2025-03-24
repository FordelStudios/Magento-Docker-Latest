<?php
namespace Formula\SkinType\Plugin;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;

class ProductDataProviderPlugin
{
    /**
     * Add skintype attribute to collection
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
            $result->addAttributeToSelect('skintype');
        }
        
        return $result;
    }
}