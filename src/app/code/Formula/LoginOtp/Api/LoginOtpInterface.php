<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Api;

interface LoginOtpInterface
{
    /**
     * Issue an OTP and deliver it to the given phone via WhatsApp.
     *
     * @param string $phone Raw user-supplied phone (will be normalized).
     * @return \Formula\LoginOtp\Api\Data\SendOtpResultInterface
     */
    public function requestOtp(string $phone);

    /**
     * Verify an OTP. On success, find-or-create the customer keyed by phone
     * and issue an {access_token, refresh_token} pair.
     *
     * `firstname` / `lastname` are accepted by the register flow so the new
     * customer record carries the user's real name from the start. They are
     * ignored when the phone already maps to an existing customer (the
     * existing name on file is not overwritten).
     *
     * @param string $phone
     * @param string $otp
     * @param string|null $firstname
     * @param string|null $lastname
     * @return \Formula\LoginOtp\Api\Data\VerifyOtpResultInterface
     */
    public function verifyOtp(string $phone, string $otp, ?string $firstname = null, ?string $lastname = null);
}
