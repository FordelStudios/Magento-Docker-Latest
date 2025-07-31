<?php
namespace Formula\RazorpayApi\Api\Data;

interface OrderResponseInterface
{
    const SUCCESS = 'success';
    const ORDER_ID = 'order_id';
    const INCREMENT_ID = 'increment_id';
    const STATUS = 'status';
    const STATE = 'state';
    const TOTAL_AMOUNT = 'total_amount';
    const CURRENCY = 'currency';
    const CREATED_AT = 'created_at';
    const RAZORPAY_PAYMENT_ID = 'razorpay_payment_id';
    const RAZORPAY_ORDER_ID = 'razorpay_order_id';
    const MESSAGE = 'message';
    const ERROR = 'error';
    const ERROR_CODE = 'error_code';

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
     * @return int|null
     */
    public function getOrderId();

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * @return string|null
     */
    public function getIncrementId();

    /**
     * @param string $incrementId
     * @return $this
     */
    public function setIncrementId($incrementId);

    /**
     * @return string|null
     */
    public function getStatus();

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return string|null
     */
    public function getState();

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state);

    /**
     * @return float|null
     */
    public function getTotalAmount();

    /**
     * @param float $totalAmount
     * @return $this
     */
    public function setTotalAmount($totalAmount);

    /**
     * @return string|null
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * @return string|null
     */
    public function getRazorpayPaymentId();

    /**
     * @param string $razorpayPaymentId
     * @return $this
     */
    public function setRazorpayPaymentId($razorpayPaymentId);

    /**
     * @return string|null
     */
    public function getRazorpayOrderId();

    /**
     * @param string $razorpayOrderId
     * @return $this
     */
    public function setRazorpayOrderId($razorpayOrderId);

    /**
     * @return string|null
     */
    public function getMessage();

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * @return bool|null
     */
    public function getError();

    /**
     * @param bool $error
     * @return $this
     */
    public function setError($error);

    /**
     * @return int|null
     */
    public function getErrorCode();

    /**
     * @param int $errorCode
     * @return $this
     */
    public function setErrorCode($errorCode);
}