<?php
declare(strict_types=1);

namespace Formula\SkinType\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SkinType extends AbstractDb
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'skintype_details';
    
    /**
     * @var string
     */
    private const PRIMARY_KEY = 'skintype_id';

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