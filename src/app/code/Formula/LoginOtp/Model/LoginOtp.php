<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Model;

use Magento\Framework\Model\AbstractModel;
use Formula\LoginOtp\Model\ResourceModel\LoginOtp as ResourceLoginOtp;

class LoginOtp extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceLoginOtp::class);
    }
}
