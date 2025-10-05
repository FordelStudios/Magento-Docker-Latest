<?php
namespace Formula\OtpValidation\Api\Data;

interface SendOtpResponseInterface
{
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const EXPIRES_IN_MINUTES = 'expires_in_minutes';
    const ERROR_CODE = 'error_code';

    /**
     * Get success status
     *
     * @return bool
     */
    public function getSuccess();

    /**
     * Set success status
     *
     * @param bool $success
     * @return $this
     */
    public function setSuccess($success);

    /**
     * Get response message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Set response message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * Get OTP expiry time in minutes
     *
     * @return int|null
     */
    public function getExpiresInMinutes();

    /**
     * Set OTP expiry time in minutes
     *
     * @param int $minutes
     * @return $this
     */
    public function setExpiresInMinutes($minutes);

    /**
     * Get error code
     *
     * @return string|null
     */
    public function getErrorCode();

    /**
     * Set error code
     *
     * @param string $errorCode
     * @return $this
     */
    public function setErrorCode($errorCode);
}