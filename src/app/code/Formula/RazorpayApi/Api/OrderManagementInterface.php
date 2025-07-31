<?php
namespace Formula\RazorpayApi\Api;

interface OrderManagementInterface
{
    /**
     * Create order with Razorpay payment
     *
     * @param string $cartId
     * @param mixed $paymentData
     * @param mixed $billingAddress
     * @return \Formula\RazorpayApi\Api\Data\OrderResponseInterface
     */
    public function createOrder($cartId, $paymentData, $billingAddress);
}