<?php
declare(strict_types=1);

namespace Formula\Ingredient\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Ingredient extends AbstractDb
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'ingredient_details';
    
    /**
     * @var string
     */
    private const PRIMARY_KEY = 'ingredient_id';

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::PRIMARY_KEY);
    }
}