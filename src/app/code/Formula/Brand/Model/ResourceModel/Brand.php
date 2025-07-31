<?php
declare(strict_types=1);

namespace Formula\Brand\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Brand extends AbstractDb
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'brand_details';
    
    /**
     * @var string
     */
    private const PRIMARY_KEY = 'brand_id';

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