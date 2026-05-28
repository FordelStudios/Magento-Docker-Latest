<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Model\ResourceModel\LoginOtp;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\LoginOtp\Model\LoginOtp as LoginOtpModel;
use Formula\LoginOtp\Model\ResourceModel\LoginOtp as LoginOtpResource;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(LoginOtpModel::class, LoginOtpResource::class);
    }
}
