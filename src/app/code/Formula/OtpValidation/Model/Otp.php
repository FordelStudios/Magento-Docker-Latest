<?php
namespace Formula\OtpValidation\Model;

use Formula\OtpValidation\Api\Data\OtpInterface;
use Magento\Framework\Model\AbstractModel;

class Otp extends AbstractModel implements OtpInterface
{
    protected function _construct()
    {
        $this->_init(\Formula\OtpValidation\Model\ResourceModel\Otp::class);
    }

    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getPhoneNumber()
    {
        return $this->getData(self::PHONE_NUMBER);
    }

    public function setPhoneNumber($phoneNumber)
    {
        return $this->setData(self::PHONE_NUMBER, $phoneNumber);
    }

    public function getOtpCode()
    {
        return $this->getData(self::OTP_CODE);
    }

    public function setOtpCode($otpCode)
    {
        return $this->setData(self::OTP_CODE, $otpCode);
    }

    public function getExpiresAt()
    {
        return $this->getData(self::EXPIRES_AT);
    }

    public function setExpiresAt($expiresAt)
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
    }

    public function getIsVerified()
    {
        return $this->getData(self::IS_VERIFIED);
    }

    public function setIsVerified($isVerified)
    {
        return $this->setData(self::IS_VERIFIED, $isVerified);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}