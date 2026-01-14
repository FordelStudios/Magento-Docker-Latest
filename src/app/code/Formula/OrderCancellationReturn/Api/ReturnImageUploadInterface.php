<?php
namespace Formula\OrderCancellationReturn\Api;

interface ReturnImageUploadInterface
{
    /**
     * Upload return images for an order
     *
     * @param int $customerId Customer ID
     * @param int $orderId Order ID
     * @param string[] $images Array of base64 encoded images
     * @return string[] Array of uploaded image paths
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadImages($customerId, $orderId, array $images);
}
