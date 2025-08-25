<?php
namespace Formula\BodyConcern\Model\ResourceModel\BodyConcern;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\BodyConcern\Model\BodyConcern as Model;
use Formula\BodyConcern\Model\ResourceModel\BodyConcern as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'bodyconcern_id';

    protected function _construct()
    {
        $this->_init(
            \Formula\BodyConcern\Model\BodyConcern::class,
            \Formula\BodyConcern\Model\ResourceModel\BodyConcern::class
        );
    }
}