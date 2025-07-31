<?php
namespace Formula\RefreshToken\Model\ResourceModel\RefreshToken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\RefreshToken\Model\RefreshToken as Model;
use Formula\RefreshToken\Model\ResourceModel\RefreshToken as ResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
