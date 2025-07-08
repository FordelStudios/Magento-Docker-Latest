<?php
namespace Formula\RazorpayApi\Model\Data;

use Formula\RazorpayApi\Api\Data\OrderResponseInterface;
use Magento\Framework\DataObject;

class OrderResponse extends DataObject implements OrderResponseInterface
{
    /**
     * @return bool
     */
    public function getSuccess()
    {
        return $this->getData(self::SUCCESS);
    }

    /**
     * @param bool $success
     * @return $this
     */
    public function setSuccess($success)
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @return string|null
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * @param string $incrementId
     * @return $this
     */
    public function setIncrementId($incrementId)
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * @return string|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @return string|null
     */
    public function getState()
    {
        return $this->getData(self::STATE);
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        return $this->setData(self::STATE, $state);
    }

    /**
     * @return float|null
     */
    public function getTotalAmount()
    {
        return $this->getData(self::TOTAL_AMOUNT);
    }

    /**
     * @param float $totalAmount
     * @return $this
     */
    public function setTotalAmount($totalAmount)
    {
        return $this->setData(self::TOTAL_AMOUNT, $totalAmount);
    }

    /**
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->getData(self::CURRENCY);
    }

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    /**
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @return string|null
     */
    public function getRazorpayPaymentId()
    {
        return $this->getData(self::RAZORPAY_PAYMENT_ID);
    }

    /**
     * @param string $razorpayPaymentId
     * @return $this
     */
    public function setRazorpayPaymentId($razorpayPaymentId)
    {
        return $this->setData(self::RAZORPAY_PAYMENT_ID, $razorpayPaymentId);
    }

    /**
     * @return string|null
     */
    public function getRazorpayOrderId()
    {
        return $this->getData(self::RAZORPAY_ORDER_ID);
    }

    /**
     * @param string $razorpayOrderId
     * @return $this
     */
    public function setRazorpayOrderId($razorpayOrderId)
    {
        return $this->setData(self::RAZORPAY_ORDER_ID, $razorpayOrderId);
    }

    /**
     * @return string|null
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @return bool|null
     */
    public function getError()
    {
        return $this->getData(self::ERROR);
    }

    /**
     * @param bool $error
     * @return $this
     */
    public function setError($error)
    {
        return $this->setData(self::ERROR, $error);
    }

    /**
     * @return int|null
     */
    public function getErrorCode()
    {
        return $this->getData(self::ERROR_CODE);
    }

    /**
     * @param int $errorCode
     * @return $this
     */
    public function setErrorCode($errorCode)
    {
        return $this->setData(self::ERROR_CODE, $errorCode);
    }
}