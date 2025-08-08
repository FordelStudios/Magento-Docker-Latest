<?php
namespace Formula\BulkCartItemsAdd\Api;

use Formula\BulkCartItemsAdd\Api\Data\BulkAddResponseInterface;

interface BulkCartItemsAddInterface
{
    /**
     * Add multiple items to logged-in customer's cart
     *
     * @return \Formula\BulkCartItemsAdd\Api\Data\BulkAddResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addCustomerCartItems(): BulkAddResponseInterface;
}
