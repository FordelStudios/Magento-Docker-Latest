<?php
namespace Formula\CashOnDeliveryApi\Api\Data;

interface CodOrderResponseInterface
{
    const SUCCESS = 'success';
    const ORDER_ID = 'order_id';
    const INCREMENT_ID = 'increment_id';
    const STATUS = 'status';
    const STATE = 'state';
    const TOTAL_AMOUNT = 'total_amount';
    const CURRENCY = 'currency';
    const CREATED_AT = 'created_at';
    const PAYMENT_METHOD = 'payment_method';
    const MESSAGE = 'message';
    const ERROR = 'error';
    const ERROR_CODE = 'error_code';
    const SHIPROCKET_ORDER_ID = 'shiprocket_order_id';
    const SHIPROCKET_SHIPMENT_ID = 'shiprocket_shipment_id';
    const SHIPROCKET_AWB_NUMBER = 'shiprocket_awb_number';
    const SHIPROCKET_COURIER_NAME = 'shiprocket_courier_name';

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
    public function getPaymentMethod();

    /**
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod);

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

    /**
     * @return string|null
     */
    public function getShiprocketOrderId();

    /**
     * @param string $shiprocketOrderId
     * @return $this
     */
    public function setShiprocketOrderId($shiprocketOrderId);

    /**
     * @return string|null
     */
    public function getShiprocketShipmentId();

    /**
     * @param string $shiprocketShipmentId
     * @return $this
     */
    public function setShiprocketShipmentId($shiprocketShipmentId);

    /**
     * @return string|null
     */
    public function getShiprocketAwbNumber();

    /**
     * @param string $shiprocketAwbNumber
     * @return $this
     */
    public function setShiprocketAwbNumber($shiprocketAwbNumber);

    /**
     * @return string|null
     */
    public function getShiprocketCourierName();

    /**
     * @param string $shiprocketCourierName
     * @return $this
     */
    public function setShiprocketCourierName($shiprocketCourierName);
}