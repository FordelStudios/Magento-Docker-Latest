<?php
namespace Formula\OtpValidation\Model\Data;

use Formula\OtpValidation\Api\Data\SendOtpResponseInterface;
use Magento\Framework\DataObject;

class SendOtpResponse extends DataObject implements SendOtpResponseInterface
{
    /**
     * Get success status
     *
     * @return bool
     */
    public function getSuccess()
    {
        return (bool) $this->getData(self::SUCCESS);
    }

    /**
     * Set success status
     *
     * @param bool $success
     * @return $this
     */
    public function setSuccess($success)
    {
        return $this->setData(self::SUCCESS, (bool) $success);
    }

    /**
     * Get response message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Set response message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * Get OTP expiry time in minutes
     *
     * @return int|null
     */
    public function getExpiresInMinutes()
    {
        return $this->getData(self::EXPIRES_IN_MINUTES);
    }

    /**
     * Set OTP expiry time in minutes
     *
     * @param int $minutes
     * @return $this
     */
    public function setExpiresInMinutes($minutes)
    {
        return $this->setData(self::EXPIRES_IN_MINUTES, (int) $minutes);
    }

    /**
     * Get error code
     *
     * @return string|null
     */
    public function getErrorCode()
    {
        return $this->getData(self::ERROR_CODE);
    }

    /**
     * Set error code
     *
     * @param string $errorCode
     * @return $this
     */
    public function setErrorCode($errorCode)
    {
        return $this->setData(self::ERROR_CODE, $errorCode);
    }
}