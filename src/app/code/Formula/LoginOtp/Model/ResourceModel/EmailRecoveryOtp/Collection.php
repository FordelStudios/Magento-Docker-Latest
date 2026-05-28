<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Model\ResourceModel\EmailRecoveryOtp;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\LoginOtp\Model\EmailRecoveryOtp as EmailRecoveryOtpModel;
use Formula\LoginOtp\Model\ResourceModel\EmailRecoveryOtp as EmailRecoveryOtpResource;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(EmailRecoveryOtpModel::class, EmailRecoveryOtpResource::class);
    }
}
