<?php
namespace Formula\CashOnDeliveryApi\Api;

interface CodOrderManagementInterface
{
    /**
     * Create order with Cash on Delivery payment
     *
     * @param string $cartId
     * @param mixed $billingAddress
     * @param mixed $shippingAddress
     * @return \Formula\CashOnDeliveryApi\Api\Data\CodOrderResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createOrder($cartId, $billingAddress, $shippingAddress = null);
}