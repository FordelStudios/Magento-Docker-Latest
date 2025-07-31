<?php
namespace Formula\RefreshToken\Model;

use Magento\Framework\Model\AbstractModel;
use Formula\RefreshToken\Api\Data\RefreshTokenInterface;
use Formula\RefreshToken\Model\ResourceModel\RefreshToken as ResourceModel;

class RefreshToken extends AbstractModel implements RefreshTokenInterface
{
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    public function getId() { return $this->getData(self::ID); }
    public function getCustomerId() { return $this->getData(self::CUSTOMER_ID); }
    public function getToken() { return $this->getData(self::TOKEN); }
    public function getExpiresAt() { return $this->getData(self::EXPIRES_AT); }

    public function setCustomerId($customerId) { return $this->setData(self::CUSTOMER_ID, $customerId); }
    public function setToken($token) { return $this->setData(self::TOKEN, $token); }
    public function setExpiresAt($expiresAt) { return $this->setData(self::EXPIRES_AT, $expiresAt); }
}
