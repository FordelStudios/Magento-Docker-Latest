<?php
namespace Formula\OrderCancellationReturn\Model\Data;

use Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface;
use Magento\Framework\DataObject;

class RefundResponse extends DataObject implements RefundResponseInterface
{
    /**
     * Get success status
     *
     * @return bool
     */
    public function getSuccess()
    {
        return $this->getData(self::SUCCESS);
    }

    /**
     * Set success status
     *
     * @param bool $success
     * @return $this
     */
    public function setSuccess($success)
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * Get error status
     *
     * @return bool
     */
    public function getError()
    {
        return $this->getData(self::ERROR);
    }

    /**
     * Set error status
     *
     * @param bool $error
     * @return $this
     */
    public function setError($error)
    {
        return $this->setData(self::ERROR, $error);
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * Get order ID
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get increment ID
     *
     * @return string
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * Set increment ID
     *
     * @param string $incrementId
     * @return $this
     */
    public function setIncrementId($incrementId)
    {
        return $this->setData(self::INCREMENT_ID, $incrementId);
    }

    /**
     * Get refund amount
     *
     * @return float
     */
    public function getRefundAmount()
    {
        return $this->getData(self::REFUND_AMOUNT);
    }

    /**
     * Set refund amount
     *
     * @param float $amount
     * @return $this
     */
    public function setRefundAmount($amount)
    {
        return $this->setData(self::REFUND_AMOUNT, $amount);
    }

    /**
     * Get refund method
     *
     * @return string
     */
    public function getRefundMethod()
    {
        return $this->getData(self::REFUND_METHOD);
    }

    /**
     * Set refund method
     *
     * @param string $method
     * @return $this
     */
    public function setRefundMethod($method)
    {
        return $this->setData(self::REFUND_METHOD, $method);
    }

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getData(self::TRANSACTION_ID);
    }

    /**
     * Set transaction ID
     *
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        return $this->setData(self::TRANSACTION_ID, $transactionId);
    }
}