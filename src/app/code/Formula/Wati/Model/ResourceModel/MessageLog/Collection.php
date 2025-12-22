<?php
declare(strict_types=1);

namespace Formula\Wati\Model\ResourceModel\MessageLog;

use Formula\Wati\Model\MessageLog;
use Formula\Wati\Model\ResourceModel\MessageLog as MessageLogResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Wati message log collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'log_id';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(MessageLog::class, MessageLogResource::class);
    }
}
