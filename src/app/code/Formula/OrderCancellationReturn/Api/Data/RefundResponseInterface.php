<?php
namespace Formula\OrderCancellationReturn\Api\Data;

interface RefundResponseInterface
{
    const SUCCESS = 'success';
    const ERROR = 'error';
    const MESSAGE = 'message';
    const ORDER_ID = 'order_id';
    const INCREMENT_ID = 'increment_id';
    const REFUND_AMOUNT = 'refund_amount';
    const REFUND_METHOD = 'refund_method';
    const TRANSACTION_ID = 'transaction_id';

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
     * Get error status
     *
     * @return bool
     */
    public function getError();

    /**
     * Set error status
     *
     * @param bool $error
     * @return $this
     */
    public function setError($error);

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * Get order ID
     *
     * @return int
     */
    public function getOrderId();

    /**
     * Set order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get increment ID
     *
     * @return string
     */
    public function getIncrementId();

    /**
     * Set increment ID
     *
     * @param string $incrementId
     * @return $this
     */
    public function setIncrementId($incrementId);

    /**
     * Get refund amount
     *
     * @return float
     */
    public function getRefundAmount();

    /**
     * Set refund amount
     *
     * @param float $amount
     * @return $this
     */
    public function setRefundAmount($amount);

    /**
     * Get refund method
     *
     * @return string
     */
    public function getRefundMethod();

    /**
     * Set refund method
     *
     * @param string $method
     * @return $this
     */
    public function setRefundMethod($method);

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionId();

    /**
     * Set transaction ID
     *
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId);
}