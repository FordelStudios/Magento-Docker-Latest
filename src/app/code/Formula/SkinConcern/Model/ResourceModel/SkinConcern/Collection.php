<?php
namespace Formula\SkinConcern\Model\ResourceModel\SkinConcern;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\SkinConcern\Model\SkinConcern as Model;
use Formula\SkinConcern\Model\ResourceModel\SkinConcern as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'skinconcern_id';

    protected function _construct()
    {
        $this->_init(
            \Formula\SkinConcern\Model\SkinConcern::class,
            \Formula\SkinConcern\Model\ResourceModel\SkinConcern::class
        );
    }
}