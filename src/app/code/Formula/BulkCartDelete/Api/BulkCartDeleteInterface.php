<?php 

// File: Api/BulkCartDeleteInterface.php

namespace Formula\BulkCartDelete\Api;

use Formula\BulkCartDelete\Api\Data\BulkDeleteResponseInterface;

/**
 * Interface for bulk cart item deletion
 */
interface BulkCartDeleteInterface
{
    /**
     * Delete multiple cart items for logged-in customer
     *
     * @return \Formula\BulkCartDelete\Api\Data\BulkDeleteResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteCustomerCartItems(): BulkDeleteResponseInterface;

    /**
     * Delete multiple cart items for guest cart
     *
     * @param string $cartId
     * @return \Formula\BulkCartDelete\Api\Data\BulkDeleteResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteGuestCartItems(string $cartId): BulkDeleteResponseInterface;
}
