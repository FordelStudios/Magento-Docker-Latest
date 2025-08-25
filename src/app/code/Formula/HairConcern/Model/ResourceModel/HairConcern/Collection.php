<?php
namespace Formula\HairConcern\Model\ResourceModel\HairConcern;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\HairConcern\Model\HairConcern as Model;
use Formula\HairConcern\Model\ResourceModel\HairConcern as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'hairconcern_id';

    protected function _construct()
    {
        $this->_init(
            \Formula\HairConcern\Model\HairConcern::class,
            \Formula\HairConcern\Model\ResourceModel\HairConcern::class
        );
    }
}