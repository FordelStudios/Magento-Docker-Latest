<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Api;

interface EmailRecoveryInterface
{
    /**
     * Send an OTP to the customer's email. Always returns success even if no
     * customer exists with that email (so we don't leak account existence).
     *
     * @param string $email
     * @return \Formula\LoginOtp\Api\Data\SendOtpResultInterface
     */
    public function requestEmailOtp(string $email);

    /**
     * Verify the email OTP. On success, returns a short-lived `phone_add_token`
     * the caller must present to /add-phone within 5 minutes.
     *
     * @param string $email
     * @param string $otp
     * @return \Formula\LoginOtp\Api\Data\VerifyEmailOtpResultInterface
     */
    public function verifyEmailOtp(string $email, string $otp);

    /**
     * Attach a phone to an account using a previously-issued phone_add_token.
     * Triggers a phone-OTP send + verification in a single step.
     *
     * Flow expected by caller:
     *   1. POST /add-phone-request {phone_add_token, phone}  → sends phone OTP
     *   2. POST /add-phone-confirm {phone_add_token, phone, otp}  → links + returns token
     *
     * We model this as two API methods to keep each one simple.
     *
     * @param string $phoneAddToken
     * @param string $phone
     * @return \Formula\LoginOtp\Api\Data\SendOtpResultInterface
     */
    public function addPhoneRequest(string $phoneAddToken, string $phone);

    /**
     * @param string $phoneAddToken
     * @param string $phone
     * @param string $otp
     * @return \Formula\LoginOtp\Api\Data\VerifyOtpResultInterface
     */
    public function addPhoneConfirm(string $phoneAddToken, string $phone, string $otp);
}
