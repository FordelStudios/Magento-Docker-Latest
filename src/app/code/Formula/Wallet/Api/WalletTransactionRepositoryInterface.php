<?php
namespace Formula\Wallet\Api;

use Formula\Wallet\Api\Data\WalletTransactionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface WalletTransactionRepositoryInterface
{
    /**
     * Save transaction
     *
     * @param WalletTransactionInterface $transaction
     * @return WalletTransactionInterface
     * @throws LocalizedException
     */
    public function save(WalletTransactionInterface $transaction);

    /**
     * Get transaction by ID
     *
     * @param int $transactionId
     * @return WalletTransactionInterface
     * @throws NoSuchEntityException
     */
    public function getById($transactionId);

    /**
     * Get list of transactions
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete transaction
     *
     * @param WalletTransactionInterface $transaction
     * @return bool
     * @throws LocalizedException
     */
    public function delete(WalletTransactionInterface $transaction);

    /**
     * Delete transaction by ID
     *
     * @param int $transactionId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($transactionId);

    /**
     * Create and save a new transaction
     *
     * @param int $customerId
     * @param float $amount
     * @param string $type
     * @param float $balanceBefore
     * @param float $balanceAfter
     * @param string|null $description
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @return WalletTransactionInterface
     * @throws LocalizedException
     */
    public function createTransaction(
        $customerId,
        $amount,
        $type,
        $balanceBefore,
        $balanceAfter,
        $description = null,
        $referenceType = null,
        $referenceId = null
    );
}
