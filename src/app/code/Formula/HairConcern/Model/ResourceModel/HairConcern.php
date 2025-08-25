<?php
declare(strict_types=1);

namespace Formula\HairConcern\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class HairConcern extends AbstractDb
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'hairconcern_details';
    
    /**
     * @var string
     */
    private const PRIMARY_KEY = 'hairconcern_id';

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