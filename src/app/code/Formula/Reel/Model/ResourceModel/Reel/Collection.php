<?php
namespace Formula\Reel\Model\ResourceModel\Reel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\Reel\Model\Reel as Model;
use Formula\Reel\Model\ResourceModel\Reel as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'reel_id';

    protected function _construct()
    {
        $this->_init(
            \Formula\Reel\Model\Reel::class,
            \Formula\Reel\Model\ResourceModel\Reel::class
        );
    }
}