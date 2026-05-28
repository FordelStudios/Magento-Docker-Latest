<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Api\Data;

interface SendOtpResultInterface
{
    /**
     * @return bool
     */
    public function getSuccess();

    /**
     * @param bool $success
     * @return $this
     */
    public function setSuccess($success);

    /**
     * Seconds until the OTP expires (from time of issue).
     * @return int
     */
    public function getExpiresIn();

    /**
     * @param int $expiresIn
     * @return $this
     */
    public function setExpiresIn($expiresIn);

    /**
     * Optional human-readable message (e.g. "Sent on WhatsApp to +91 9876543210").
     * @return string|null
     */
    public function getMessage();

    /**
     * @param string|null $message
     * @return $this
     */
    public function setMessage($message);
}
