<?php
declare(strict_types=1);

namespace Formula\BodyConcern\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BodyConcern extends AbstractDb
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'bodyconcern_details';
    
    /**
     * @var string
     */
    private const PRIMARY_KEY = 'bodyconcern_id';

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