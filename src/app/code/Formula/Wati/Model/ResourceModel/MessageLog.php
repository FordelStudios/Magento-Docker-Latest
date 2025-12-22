<?php
declare(strict_types=1);

namespace Formula\Wati\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Wati message log resource model
 */
class MessageLog extends AbstractDb
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init('formula_wati_message_log', 'log_id');
    }
}
