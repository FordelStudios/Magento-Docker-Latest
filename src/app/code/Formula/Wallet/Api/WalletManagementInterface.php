<?php
namespace Formula\Wallet\Api;

interface WalletManagementInterface
{
    /**
     * Get customer wallet balance
     *
     * @param int $customerId
     * @return float
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getWalletBalance($customerId);

    /**
     * Apply wallet balance to cart
     *
     * @param int $customerId
     * @param int $cartId
     * @param float $amount
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function applyWalletToCart($customerId, $cartId, $amount = null);

    /**
     * Remove wallet balance from cart
     *
     * @param int $customerId
     * @param int $cartId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeWalletFromCart($customerId, $cartId);

    /**
     * Place order using wallet balance
     *
     * @param int $customerId
     * @param int $cartId
     * @return \Formula\RazorpayApi\Api\Data\OrderResponseInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function placeOrderWithWallet($customerId, $cartId);

    /**
     * Update customer wallet balance (Admin only)
     *
     * @param int $customerId
     * @param float $amount
     * @param string $action add|subtract|set
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateWalletBalance($customerId, $amount, $action = 'add');

    /**
     * Get customer wallet transaction history
     *
     * @param int $customerId
     * @param int $pageSize
     * @param int $currentPage
     * @return \Magento\Framework\Api\SearchResultsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTransactionHistory($customerId, $pageSize = 20, $currentPage = 1);
}