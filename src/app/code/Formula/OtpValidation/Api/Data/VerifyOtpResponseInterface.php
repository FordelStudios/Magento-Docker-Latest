<?php
namespace Formula\OtpValidation\Api\Data;

interface VerifyOtpResponseInterface
{
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const VERIFIED = 'verified';
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
     * Get verification status
     *
     * @return bool
     */
    public function getVerified();

    /**
     * Set verification status
     *
     * @param bool $verified
     * @return $this
     */
    public function setVerified($verified);

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