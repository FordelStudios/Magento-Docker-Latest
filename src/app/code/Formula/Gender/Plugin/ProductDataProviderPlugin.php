<?php

namespace Formula\Gender\Plugin;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;

class ProductDataProviderPlugin
{
    /**
     * Add gender attribute to data provider
     *
     * @param ProductDataProvider $subject
     * @param $result
     * @return mixed
     */
    public function afterGetCollection(ProductDataProvider $subject, $result)
    {
        $result->addAttributeToSelect('gender');
        return $result;
    }
}