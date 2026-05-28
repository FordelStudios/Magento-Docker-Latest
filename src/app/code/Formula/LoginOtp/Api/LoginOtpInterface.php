<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Api;

interface LoginOtpInterface
{
    /**
     * Issue an OTP and deliver it to the given phone via WhatsApp.
     *
     * `mode` enforces strict separation between login and register so users
     * can't accidentally drift between flows and OTP messages aren't wasted
     * on numbers that will fail at verify anyway:
     *   - 'login'    : require an existing customer; reject if not found
     *   - 'register' : require NO existing customer; reject if found
     *   - (omitted)  : back-compat permissive mode (find-or-create on verify)
     *
     * @param string $phone Raw user-supplied phone (will be normalized).
     * @param string|null $mode 'login' | 'register' | null (permissive)
     * @return \Formula\LoginOtp\Api\Data\SendOtpResultInterface
     */
    public function requestOtp(string $phone, ?string $mode = null);

    /**
     * Verify an OTP. On success, issue an {access_token, refresh_token} pair.
     *
     * Behavior by `mode`:
     *   - 'login'    : strict — require an existing customer; reject otherwise.
     *                  firstname / lastname ignored.
     *   - 'register' : strict — require NO existing customer; reject otherwise.
     *                  Create the customer with firstname / lastname applied.
     *   - (omitted)  : back-compat find-or-create. firstname / lastname applied
     *                  only on the create branch.
     *
     * @param string $phone
     * @param string $otp
     * @param string|null $firstname
     * @param string|null $lastname
     * @param string|null $mode 'login' | 'register' | null (permissive)
     * @return \Formula\LoginOtp\Api\Data\VerifyOtpResultInterface
     */
    public function verifyOtp(
        string $phone,
        string $otp,
        ?string $firstname = null,
        ?string $lastname = null,
        ?string $mode = null
    );
}
