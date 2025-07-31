<?php
declare(strict_types=1);

namespace Formula\Reel\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Reel extends AbstractDb
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'reel_details';
    
    /**
     * @var string
     */
    private const PRIMARY_KEY = 'reel_id';

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