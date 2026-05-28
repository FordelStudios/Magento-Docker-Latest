<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class EmailRecoveryOtp extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('formula_email_recovery_otp', 'entity_id');
    }
}
