<?php
declare(strict_types=1);

namespace Formula\SkinConcern\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SkinConcern extends AbstractDb
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'skinconcern_details';
    
    /**
     * @var string
     */
    private const PRIMARY_KEY = 'skinconcern_id';

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