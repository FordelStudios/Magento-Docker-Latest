<?php
declare(strict_types=1);

namespace Formula\Wati\Model;

use Formula\Wati\Api\Data\MessageLogInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Wati message log model
 */
class MessageLog extends AbstractModel implements MessageLogInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(\Formula\Wati\Model\ResourceModel\MessageLog::class);
    }

    /**
     * @inheritdoc
     */
    public function getLogId()
    {
        return $this->getData(self::LOG_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLogId($logId)
    {
        return $this->setData(self::LOG_ID, $logId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderIncrementId()
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setOrderIncrementId($orderIncrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * @inheritdoc
     */
    public function getPhoneNumber()
    {
        return $this->getData(self::PHONE_NUMBER);
    }

    /**
     * @inheritdoc
     */
    public function setPhoneNumber($phoneNumber)
    {
        return $this->setData(self::PHONE_NUMBER, $phoneNumber);
    }

    /**
     * @inheritdoc
     */
    public function getTemplateName()
    {
        return $this->getData(self::TEMPLATE_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setTemplateName($templateName)
    {
        return $this->setData(self::TEMPLATE_NAME, $templateName);
    }

    /**
     * @inheritdoc
     */
    public function getOrderStatus()
    {
        return $this->getData(self::ORDER_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setOrderStatus($orderStatus)
    {
        return $this->setData(self::ORDER_STATUS, $orderStatus);
    }

    /**
     * @inheritdoc
     */
    public function getMessageId()
    {
        return $this->getData(self::MESSAGE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setMessageId($messageId)
    {
        return $this->setData(self::MESSAGE_ID, $messageId);
    }

    /**
     * @inheritdoc
     */
    public function getDeliveryStatus()
    {
        return $this->getData(self::DELIVERY_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setDeliveryStatus($deliveryStatus)
    {
        return $this->setData(self::DELIVERY_STATUS, $deliveryStatus);
    }

    /**
     * @inheritdoc
     */
    public function getRequestPayload()
    {
        return $this->getData(self::REQUEST_PAYLOAD);
    }

    /**
     * @inheritdoc
     */
    public function setRequestPayload($requestPayload)
    {
        return $this->setData(self::REQUEST_PAYLOAD, $requestPayload);
    }

    /**
     * @inheritdoc
     */
    public function getResponsePayload()
    {
        return $this->getData(self::RESPONSE_PAYLOAD);
    }

    /**
     * @inheritdoc
     */
    public function setResponsePayload($responsePayload)
    {
        return $this->setData(self::RESPONSE_PAYLOAD, $responsePayload);
    }

    /**
     * @inheritdoc
     */
    public function getErrorMessage()
    {
        return $this->getData(self::ERROR_MESSAGE);
    }

    /**
     * @inheritdoc
     */
    public function setErrorMessage($errorMessage)
    {
        return $this->setData(self::ERROR_MESSAGE, $errorMessage);
    }

    /**
     * @inheritdoc
     */
    public function getRetryCount()
    {
        return (int) $this->getData(self::RETRY_COUNT);
    }

    /**
     * @inheritdoc
     */
    public function setRetryCount($retryCount)
    {
        return $this->setData(self::RETRY_COUNT, $retryCount);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritdoc
     */
    public function getDeliveredAt()
    {
        return $this->getData(self::DELIVERED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setDeliveredAt($deliveredAt)
    {
        return $this->setData(self::DELIVERED_AT, $deliveredAt);
    }
}
