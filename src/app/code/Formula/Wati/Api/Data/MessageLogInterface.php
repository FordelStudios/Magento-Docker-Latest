<?php
declare(strict_types=1);

namespace Formula\Wati\Api\Data;

/**
 * Interface for Wati message log data
 */
interface MessageLogInterface
{
    const LOG_ID = 'log_id';
    const ORDER_ID = 'order_id';
    const ORDER_INCREMENT_ID = 'order_increment_id';
    const PHONE_NUMBER = 'phone_number';
    const TEMPLATE_NAME = 'template_name';
    const ORDER_STATUS = 'order_status';
    const MESSAGE_ID = 'message_id';
    const DELIVERY_STATUS = 'delivery_status';
    const REQUEST_PAYLOAD = 'request_payload';
    const RESPONSE_PAYLOAD = 'response_payload';
    const ERROR_MESSAGE = 'error_message';
    const RETRY_COUNT = 'retry_count';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELIVERED_AT = 'delivered_at';

    /**
     * Get log ID
     *
     * @return int|null
     */
    public function getLogId();

    /**
     * Set log ID
     *
     * @param int $logId
     * @return $this
     */
    public function setLogId($logId);

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
     * Get order increment ID
     *
     * @return string
     */
    public function getOrderIncrementId();

    /**
     * Set order increment ID
     *
     * @param string $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId($orderIncrementId);

    /**
     * Get phone number
     *
     * @return string
     */
    public function getPhoneNumber();

    /**
     * Set phone number
     *
     * @param string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber($phoneNumber);

    /**
     * Get template name
     *
     * @return string
     */
    public function getTemplateName();

    /**
     * Set template name
     *
     * @param string $templateName
     * @return $this
     */
    public function setTemplateName($templateName);

    /**
     * Get order status
     *
     * @return string
     */
    public function getOrderStatus();

    /**
     * Set order status
     *
     * @param string $orderStatus
     * @return $this
     */
    public function setOrderStatus($orderStatus);

    /**
     * Get message ID
     *
     * @return string|null
     */
    public function getMessageId();

    /**
     * Set message ID
     *
     * @param string|null $messageId
     * @return $this
     */
    public function setMessageId($messageId);

    /**
     * Get delivery status
     *
     * @return string|null
     */
    public function getDeliveryStatus();

    /**
     * Set delivery status
     *
     * @param string|null $deliveryStatus
     * @return $this
     */
    public function setDeliveryStatus($deliveryStatus);

    /**
     * Get request payload
     *
     * @return string|null
     */
    public function getRequestPayload();

    /**
     * Set request payload
     *
     * @param string|null $requestPayload
     * @return $this
     */
    public function setRequestPayload($requestPayload);

    /**
     * Get response payload
     *
     * @return string|null
     */
    public function getResponsePayload();

    /**
     * Set response payload
     *
     * @param string|null $responsePayload
     * @return $this
     */
    public function setResponsePayload($responsePayload);

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getErrorMessage();

    /**
     * Set error message
     *
     * @param string|null $errorMessage
     * @return $this
     */
    public function setErrorMessage($errorMessage);

    /**
     * Get retry count
     *
     * @return int
     */
    public function getRetryCount();

    /**
     * Set retry count
     *
     * @param int $retryCount
     * @return $this
     */
    public function setRetryCount($retryCount);

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created at
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated at
     *
     * @param string|null $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get delivered at
     *
     * @return string|null
     */
    public function getDeliveredAt();

    /**
     * Set delivered at
     *
     * @param string|null $deliveredAt
     * @return $this
     */
    public function setDeliveredAt($deliveredAt);
}
