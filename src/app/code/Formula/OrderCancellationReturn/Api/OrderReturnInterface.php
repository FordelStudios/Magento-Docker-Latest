<?php
namespace Formula\OrderCancellationReturn\Api;

use Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface;

interface OrderReturnInterface
{
    /**
     * Return an order for a customer
     *
     * @param int $customerId Customer ID
     * @param int $orderId Order ID to return
     * @param string|null $reason Optional reason for return
     * @param string[]|null $images Optional array of image paths (from upload endpoint)
     * @param int|null $pickupAddressId Optional pickup address ID
     * @return \Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function returnOrder($customerId, $orderId, $reason = null, $images = null, $pickupAddressId = null);
}