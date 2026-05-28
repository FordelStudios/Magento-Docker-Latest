<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class LoginOtp extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('formula_login_otp', 'entity_id');
    }
}
