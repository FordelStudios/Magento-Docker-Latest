<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Model\Data;

use Formula\LoginOtp\Api\Data\VerifyOtpResultInterface;
use Magento\Framework\DataObject;

class VerifyOtpResult extends DataObject implements VerifyOtpResultInterface
{
    public function getAccessToken()
    {
        return (string) $this->_getData('access_token');
    }

    public function setAccessToken($accessToken)
    {
        return $this->setData('access_token', $accessToken);
    }

    public function getRefreshToken()
    {
        return $this->_getData('refresh_token');
    }

    public function setRefreshToken($refreshToken)
    {
        return $this->setData('refresh_token', $refreshToken);
    }

    public function getIsNewUser()
    {
        return (bool) $this->_getData('is_new_user');
    }

    public function setIsNewUser($isNewUser)
    {
        return $this->setData('is_new_user', (bool) $isNewUser);
    }

    public function getCustomerId()
    {
        return (int) $this->_getData('customer_id');
    }

    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', (int) $customerId);
    }

    public function getHasPlaceholderEmail()
    {
        return (bool) $this->_getData('has_placeholder_email');
    }

    public function setHasPlaceholderEmail($hasPlaceholderEmail)
    {
        return $this->setData('has_placeholder_email', (bool) $hasPlaceholderEmail);
    }
}
