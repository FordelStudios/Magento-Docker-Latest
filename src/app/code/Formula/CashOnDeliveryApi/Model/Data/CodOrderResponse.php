<?php
namespace Formula\CashOnDeliveryApi\Model\Data;

use Formula\CashOnDeliveryApi\Api\Data\CodOrderResponseInterface;
use Magento\Framework\DataObject;

class CodOrderResponse extends DataObject implements CodOrderResponseInterface
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
    public function getPaymentMethod()
    {
        return $this->getData(self::PAYMENT_METHOD);
    }

    /**
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
        return $this->setData(self::PAYMENT_METHOD, $paymentMethod);
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

    /**
     * @return string|null
     */
    public function getShiprocketOrderId()
    {
        return $this->getData(self::SHIPROCKET_ORDER_ID);
    }

    /**
     * @param string $shiprocketOrderId
     * @return $this
     */
    public function setShiprocketOrderId($shiprocketOrderId)
    {
        return $this->setData(self::SHIPROCKET_ORDER_ID, $shiprocketOrderId);
    }

    /**
     * @return string|null
     */
    public function getShiprocketShipmentId()
    {
        return $this->getData(self::SHIPROCKET_SHIPMENT_ID);
    }

    /**
     * @param string $shiprocketShipmentId
     * @return $this
     */
    public function setShiprocketShipmentId($shiprocketShipmentId)
    {
        return $this->setData(self::SHIPROCKET_SHIPMENT_ID, $shiprocketShipmentId);
    }

    /**
     * @return string|null
     */
    public function getShiprocketAwbNumber()
    {
        return $this->getData(self::SHIPROCKET_AWB_NUMBER);
    }

    /**
     * @param string $shiprocketAwbNumber
     * @return $this
     */
    public function setShiprocketAwbNumber($shiprocketAwbNumber)
    {
        return $this->setData(self::SHIPROCKET_AWB_NUMBER, $shiprocketAwbNumber);
    }

    /**
     * @return string|null
     */
    public function getShiprocketCourierName()
    {
        return $this->getData(self::SHIPROCKET_COURIER_NAME);
    }

    /**
     * @param string $shiprocketCourierName
     * @return $this
     */
    public function setShiprocketCourierName($shiprocketCourierName)
    {
        return $this->setData(self::SHIPROCKET_COURIER_NAME, $shiprocketCourierName);
    }
}