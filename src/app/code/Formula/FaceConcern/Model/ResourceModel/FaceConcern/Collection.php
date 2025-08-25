<?php
namespace Formula\FaceConcern\Model\ResourceModel\FaceConcern;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\FaceConcern\Model\FaceConcern as Model;
use Formula\FaceConcern\Model\ResourceModel\FaceConcern as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'faceconcern_id';

    protected function _construct()
    {
        $this->_init(
            \Formula\FaceConcern\Model\FaceConcern::class,
            \Formula\FaceConcern\Model\ResourceModel\FaceConcern::class
        );
    }
}