<?php
/**
 * Wallet Balance Service Interface
 *
 * Provides atomic wallet balance operations with row-level locking
 * to prevent race conditions during concurrent transactions.
 */
namespace Formula\Wallet\Api;

interface WalletBalanceServiceInterface
{
    /**
     * Atomically update wallet balance with row locking
     *
     * Uses SELECT FOR UPDATE to lock the row during transaction,
     * preventing race conditions when multiple requests try to
     * update the same customer's balance simultaneously.
     *
     * @param int $customerId
     * @param float $amount The amount to add/subtract/set (always positive for add/subtract)
     * @param string $operation 'add', 'subtract', or 'set'
     * @param string|null $description Optional description for transaction log
     * @param string|null $referenceType Optional reference type (order, refund, admin_api, etc.)
     * @param int|null $referenceId Optional reference ID (order_id, etc.)
     * @param int|null $adminUserId Optional admin user ID for admin-initiated transactions
     * @param string|null $adminUsername Optional admin username for audit trail
     * @return array ['old_balance' => float, 'new_balance' => float, 'transaction_id' => int|null]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateBalanceAtomic(
        int $customerId,
        float $amount,
        string $operation = 'add',
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $adminUserId = null,
        ?string $adminUsername = null
    ): array;

    /**
     * Get wallet balance with row lock for read-then-write operations
     *
     * Must be called within a transaction. The row remains locked
     * until the transaction is committed or rolled back.
     *
     * @param int $customerId
     * @return float
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBalanceWithLock(int $customerId): float;

    /**
     * Get the maximum allowed wallet balance
     *
     * @return float
     */
    public function getMaxBalanceLimit(): float;

    /**
     * Get the maximum allowed single transaction amount
     *
     * @return float
     */
    public function getMaxTransactionLimit(): float;

    /**
     * Validate that a balance update would be within limits
     *
     * @param float $currentBalance
     * @param float $amount
     * @param string $operation
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException If validation fails
     */
    public function validateBalanceUpdate(float $currentBalance, float $amount, string $operation): bool;
}
