<?php
namespace Formula\SkinType\Model\ResourceModel\SkinType;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\SkinType\Model\SkinType as Model;
use Formula\SkinType\Model\ResourceModel\SkinType as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'skintype_id';

    protected function _construct()
    {
        $this->_init(
            \Formula\SkinType\Model\SkinType::class,
            \Formula\SkinType\Model\ResourceModel\SkinType::class
        );
    }
}