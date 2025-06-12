<?php
namespace Formula\RazorpayApi\Api;

use Formula\RazorpayApi\Api\Data\RazorpayOrderDataInterface;

interface RazorpayOrderInterface
{
    /**
     * Get Razorpay order info by Magento order increment ID.
     *
     * @param string $incrementId
     * @return RazorpayOrderDataInterface
     */
    public function getByIncrementId(string $incrementId): RazorpayOrderDataInterface;
}
