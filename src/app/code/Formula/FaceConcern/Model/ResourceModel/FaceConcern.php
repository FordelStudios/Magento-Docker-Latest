<?php
declare(strict_types=1);

namespace Formula\FaceConcern\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class FaceConcern extends AbstractDb
{
    /**
     * @var string
     */
    private const TABLE_NAME = 'faceconcern_details';
    
    /**
     * @var string
     */
    private const PRIMARY_KEY = 'faceconcern_id';

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