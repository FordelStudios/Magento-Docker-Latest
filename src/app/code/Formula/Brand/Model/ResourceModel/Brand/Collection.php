<?php
namespace Formula\Brand\Model\ResourceModel\Brand;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\Brand\Model\Brand as Model;
use Formula\Brand\Model\ResourceModel\Brand as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'brand_id';

    protected function _construct()
    {
        $this->_init(
            \Formula\Brand\Model\Brand::class,
            \Formula\Brand\Model\ResourceModel\Brand::class
        );
    }
}