<?php
namespace Formula\OtpValidation\Model\Data;

use Formula\OtpValidation\Api\Data\VerifyOtpResponseInterface;
use Magento\Framework\DataObject;

class VerifyOtpResponse extends DataObject implements VerifyOtpResponseInterface
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
     * Get verification status
     *
     * @return bool
     */
    public function getVerified()
    {
        return (bool) $this->getData(self::VERIFIED);
    }

    /**
     * Set verification status
     *
     * @param bool $verified
     * @return $this
     */
    public function setVerified($verified)
    {
        return $this->setData(self::VERIFIED, (bool) $verified);
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