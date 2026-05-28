<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Model\Data;

use Formula\LoginOtp\Api\Data\VerifyEmailOtpResultInterface;
use Magento\Framework\DataObject;

class VerifyEmailOtpResult extends DataObject implements VerifyEmailOtpResultInterface
{
    public function getPhoneAddToken()
    {
        return (string) $this->_getData('phone_add_token');
    }

    public function setPhoneAddToken($phoneAddToken)
    {
        return $this->setData('phone_add_token', $phoneAddToken);
    }

    public function getExpiresIn()
    {
        return (int) $this->_getData('expires_in');
    }

    public function setExpiresIn($expiresIn)
    {
        return $this->setData('expires_in', (int) $expiresIn);
    }
}
