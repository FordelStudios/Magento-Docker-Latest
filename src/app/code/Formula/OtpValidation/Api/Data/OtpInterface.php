<?php
namespace Formula\OtpValidation\Api\Data;

interface OtpInterface
{
    const ENTITY_ID = 'entity_id';
    const CUSTOMER_ID = 'customer_id';
    const PHONE_NUMBER = 'phone_number';
    const OTP_CODE = 'otp_code';
    const EXPIRES_AT = 'expires_at';
    const IS_VERIFIED = 'is_verified';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getEntityId();
    public function setEntityId($entityId);
    public function getCustomerId();
    public function setCustomerId($customerId);
    public function getPhoneNumber();
    public function setPhoneNumber($phoneNumber);
    public function getOtpCode();
    public function setOtpCode($otpCode);
    public function getExpiresAt();
    public function setExpiresAt($expiresAt);
    public function getIsVerified();
    public function setIsVerified($isVerified);
    public function getCreatedAt();
    public function setCreatedAt($createdAt);
    public function getUpdatedAt();
    public function setUpdatedAt($updatedAt);
}