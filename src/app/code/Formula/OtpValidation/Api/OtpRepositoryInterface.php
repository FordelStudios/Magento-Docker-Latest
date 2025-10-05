<?php
namespace Formula\OtpValidation\Api;

use Formula\OtpValidation\Api\Data\OtpInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface OtpRepositoryInterface
{
    public function save(OtpInterface $otp);
    public function getById($otpId);
    public function delete(OtpInterface $otp);
    public function deleteById($otpId);
    public function getByCustomerIdAndPhone($customerId, $phoneNumber);
}