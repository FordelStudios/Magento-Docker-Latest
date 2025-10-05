<?php
namespace Formula\OtpValidation\Api;

use Formula\OtpValidation\Api\Data\SendOtpResponseInterface;
use Formula\OtpValidation\Api\Data\VerifyOtpResponseInterface;

interface OtpValidationInterface
{
    /**
     * Send OTP to phone number
     *
     * @param string $phoneNumber
     * @return \Formula\OtpValidation\Api\Data\SendOtpResponseInterface
     */
    public function sendOtp($phoneNumber);

    /**
     * Verify OTP
     *
     * @param string $phoneNumber
     * @param string $otpCode
     * @return \Formula\OtpValidation\Api\Data\VerifyOtpResponseInterface
     */
    public function verifyOtp($phoneNumber, $otpCode);
}