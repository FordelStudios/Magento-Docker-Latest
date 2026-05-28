<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Model\Data;

use Formula\LoginOtp\Api\Data\SendOtpResultInterface;
use Magento\Framework\DataObject;

class SendOtpResult extends DataObject implements SendOtpResultInterface
{
    public function getSuccess()
    {
        return (bool) $this->_getData('success');
    }

    public function setSuccess($success)
    {
        return $this->setData('success', (bool) $success);
    }

    public function getExpiresIn()
    {
        return (int) $this->_getData('expires_in');
    }

    public function setExpiresIn($expiresIn)
    {
        return $this->setData('expires_in', (int) $expiresIn);
    }

    public function getMessage()
    {
        return $this->_getData('message');
    }

    public function setMessage($message)
    {
        return $this->setData('message', $message);
    }
}
