<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Api\Data;

interface VerifyEmailOtpResultInterface
{
    /**
     * Short-lived (5 min) token the caller must present to /add-phone-*
     * endpoints to prove they verified ownership of the email address.
     * @return string
     */
    public function getPhoneAddToken();

    /**
     * @param string $phoneAddToken
     * @return $this
     */
    public function setPhoneAddToken($phoneAddToken);

    /**
     * Seconds until the phone_add_token expires.
     * @return int
     */
    public function getExpiresIn();

    /**
     * @param int $expiresIn
     * @return $this
     */
    public function setExpiresIn($expiresIn);
}
