<?php
namespace Formula\OtpValidation\Api;

interface SmsServiceInterface
{
    /**
     * Send SMS message
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    public function sendSms($phoneNumber, $message);

    /**
     * Send OTP SMS
     *
     * @param string $phoneNumber
     * @param string $otpCode
     * @return array
     */
    public function sendOtp($phoneNumber, $otpCode);

    /**
     * Validate Indian mobile number
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function isValidIndianMobile($phoneNumber);
}