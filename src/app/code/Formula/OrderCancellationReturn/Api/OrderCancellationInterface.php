<?php
namespace Formula\OrderCancellationReturn\Api;

use Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface;

interface OrderCancellationInterface
{
    /**
     * Cancel an order for a customer
     *
     * @param int $customerId Customer ID
     * @param int $orderId Order ID to cancel
     * @param string|null $reason Optional reason for cancellation
     * @return \Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function cancelOrder($customerId, $orderId, $reason = null);
}