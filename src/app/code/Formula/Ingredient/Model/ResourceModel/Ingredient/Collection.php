<?php
namespace Formula\Ingredient\Model\ResourceModel\Ingredient;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\Ingredient\Model\Ingredient as Model;
use Formula\Ingredient\Model\ResourceModel\Ingredient as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'ingredient_id';

    protected function _construct()
    {
        $this->_init(
            \Formula\Ingredient\Model\Ingredient::class,
            \Formula\Ingredient\Model\ResourceModel\Ingredient::class
        );
    }
}