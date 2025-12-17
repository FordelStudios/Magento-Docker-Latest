<?php
/**
 * Wallet Balance Service - Atomic balance operations with row locking
 *
 * This service provides thread-safe wallet balance operations using
 * SELECT FOR UPDATE to prevent race conditions during concurrent requests.
 * Transaction logging is included in the same database transaction to ensure
 * atomic operations (balance update + logging either both succeed or both fail).
 */
namespace Formula\Wallet\Model;

use Formula\Wallet\Api\WalletBalanceServiceInterface;
use Formula\Wallet\Api\Data\WalletTransactionInterface;
use Formula\Wallet\Api\WalletTransactionRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class WalletBalanceService implements WalletBalanceServiceInterface
{
    private const CONFIG_PATH_MAX_BALANCE = 'formula_wallet/limits/max_balance';
    private const CONFIG_PATH_MAX_TRANSACTION = 'formula_wallet/limits/max_transaction';
    private const DEFAULT_MAX_BALANCE = 1000000.00;      // 10 Lakhs
    private const DEFAULT_MAX_TRANSACTION = 100000.00;   // 1 Lakh
    private const DECIMAL_PRECISION = 4;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @var WalletTransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int|null Cached attribute ID for wallet_balance
     */
    private $walletAttributeId = null;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CustomerResource $customerResource
     * @param WalletTransactionRepositoryInterface $transactionRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomerResource $customerResource,
        WalletTransactionRepositoryInterface $transactionRepository,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->customerResource = $customerResource;
        $this->transactionRepository = $transactionRepository;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
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
    ): array {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            $attributeId = $this->getWalletAttributeId();
            $table = $this->customerResource->getTable('customer_entity_decimal');

            // Verify customer exists
            $this->verifyCustomerExists($customerId);

            // SELECT FOR UPDATE - locks the row to prevent race conditions
            $select = $connection->select()
                ->from($table, ['value_id', 'value'])
                ->where('entity_id = ?', $customerId)
                ->where('attribute_id = ?', $attributeId)
                ->forUpdate(true);

            $result = $connection->fetchRow($select);

            $currentBalance = 0.0;
            $valueId = null;

            if ($result) {
                $currentBalance = (float)($result['value'] ?? 0);
                $valueId = $result['value_id'];
            }

            // Validate the operation
            $this->validateBalanceUpdate($currentBalance, $amount, $operation);

            // Calculate new balance
            $newBalance = $this->calculateNewBalance($currentBalance, $amount, $operation);

            // Round to proper precision
            $newBalance = round($newBalance, self::DECIMAL_PRECISION);

            // Update or insert the balance
            if ($valueId) {
                // Update existing record
                $connection->update(
                    $table,
                    ['value' => $newBalance],
                    ['value_id = ?' => $valueId]
                );
            } else {
                // Insert new record (customer had no wallet balance before)
                $connection->insert($table, [
                    'attribute_id' => $attributeId,
                    'store_id' => 0,
                    'entity_id' => $customerId,
                    'value' => $newBalance
                ]);
            }

            // Log the transaction (inside the same DB transaction for atomicity)
            $transactionId = null;
            if ($description !== null || $referenceType !== null) {
                $transactionType = ($operation === 'subtract' || ($operation === 'set' && $newBalance < $currentBalance))
                    ? WalletTransactionInterface::TYPE_DEBIT
                    : WalletTransactionInterface::TYPE_CREDIT;

                $transactionAmount = abs($newBalance - $currentBalance);

                if ($transactionAmount > 0) {
                    $transaction = $this->transactionRepository->createTransaction(
                        $customerId,
                        $transactionAmount,
                        $transactionType,
                        $currentBalance,
                        $newBalance,
                        $description ?? $this->getDefaultDescription($operation, $transactionAmount),
                        $referenceType,
                        $referenceId,
                        $adminUserId,
                        $adminUsername
                    );
                    $transactionId = $transaction ? $transaction->getTransactionId() : null;
                }
            }

            $connection->commit();

            $this->logger->info('Wallet balance updated atomically', [
                'customer_id' => $customerId,
                'operation' => $operation,
                'amount' => $amount,
                'old_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'transaction_id' => $transactionId
            ]);

            return [
                'old_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'transaction_id' => $transactionId
            ];

        } catch (LocalizedException $e) {
            $connection->rollBack();
            $this->logger->error('Wallet atomic update failed (validation)', [
                'customer_id' => $customerId,
                'operation' => $operation,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;

        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->logger->error('Wallet atomic update failed (exception)', [
                'customer_id' => $customerId,
                'operation' => $operation,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw new LocalizedException(
                __('Failed to update wallet balance. Please try again.'),
                $e
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function getBalanceWithLock(int $customerId): float
    {
        $connection = $this->resourceConnection->getConnection();
        $attributeId = $this->getWalletAttributeId();
        $table = $this->customerResource->getTable('customer_entity_decimal');

        $select = $connection->select()
            ->from($table, ['value'])
            ->where('entity_id = ?', $customerId)
            ->where('attribute_id = ?', $attributeId)
            ->forUpdate(true);

        $result = $connection->fetchOne($select);

        return $result !== false ? (float)$result : 0.0;
    }

    /**
     * @inheritdoc
     */
    public function getMaxBalanceLimit(): float
    {
        $configValue = $this->scopeConfig->getValue(
            self::CONFIG_PATH_MAX_BALANCE,
            ScopeInterface::SCOPE_STORE
        );

        return $configValue !== null ? (float)$configValue : self::DEFAULT_MAX_BALANCE;
    }

    /**
     * @inheritdoc
     */
    public function getMaxTransactionLimit(): float
    {
        $configValue = $this->scopeConfig->getValue(
            self::CONFIG_PATH_MAX_TRANSACTION,
            ScopeInterface::SCOPE_STORE
        );

        return $configValue !== null ? (float)$configValue : self::DEFAULT_MAX_TRANSACTION;
    }

    /**
     * @inheritdoc
     */
    public function validateBalanceUpdate(float $currentBalance, float $amount, string $operation): bool
    {
        // Validate operation type
        if (!in_array($operation, ['add', 'subtract', 'set'])) {
            throw new LocalizedException(__('Invalid operation. Use add, subtract, or set.'));
        }

        // Validate amount is numeric and not NaN/infinite
        if (!is_finite($amount)) {
            throw new LocalizedException(__('Invalid amount provided.'));
        }

        // For add/subtract, amount must be positive
        if ($operation !== 'set' && $amount < 0) {
            throw new LocalizedException(
                __('Amount must be positive. Use "subtract" action to reduce balance.')
            );
        }

        // For add/subtract, amount cannot be zero
        if ($operation !== 'set' && $amount == 0) {
            throw new LocalizedException(__('Amount cannot be zero.'));
        }

        // Check transaction limit
        $maxTransaction = $this->getMaxTransactionLimit();
        if ($amount > $maxTransaction) {
            throw new LocalizedException(
                __('Single transaction cannot exceed %1.', number_format($maxTransaction, 2))
            );
        }

        // Calculate and validate new balance
        $newBalance = $this->calculateNewBalance($currentBalance, $amount, $operation);

        // Prevent negative balance
        if ($newBalance < 0) {
            throw new LocalizedException(
                __('Insufficient wallet balance. Current: %1, Required: %2',
                    number_format($currentBalance, 2),
                    number_format($amount, 2))
            );
        }

        // Check maximum balance limit
        $maxBalance = $this->getMaxBalanceLimit();
        if ($newBalance > $maxBalance) {
            throw new LocalizedException(
                __('Wallet balance cannot exceed %1.', number_format($maxBalance, 2))
            );
        }

        return true;
    }

    /**
     * Calculate new balance based on operation
     *
     * @param float $currentBalance
     * @param float $amount
     * @param string $operation
     * @return float
     */
    private function calculateNewBalance(float $currentBalance, float $amount, string $operation): float
    {
        switch ($operation) {
            case 'add':
                return $currentBalance + $amount;
            case 'subtract':
                return $currentBalance - $amount;
            case 'set':
                return max(0, $amount);
            default:
                return $currentBalance;
        }
    }

    /**
     * Get the attribute ID for wallet_balance
     *
     * @return int
     * @throws LocalizedException
     */
    private function getWalletAttributeId(): int
    {
        if ($this->walletAttributeId === null) {
            $attribute = $this->customerResource->getAttribute('wallet_balance');
            if (!$attribute || !$attribute->getId()) {
                throw new LocalizedException(__('Wallet balance attribute not found.'));
            }
            $this->walletAttributeId = (int)$attribute->getId();
        }

        return $this->walletAttributeId;
    }

    /**
     * Verify customer exists
     *
     * @param int $customerId
     * @throws NoSuchEntityException
     */
    private function verifyCustomerExists(int $customerId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->customerResource->getTable('customer_entity');

        $exists = $connection->fetchOne(
            $connection->select()
                ->from($table, ['entity_id'])
                ->where('entity_id = ?', $customerId)
        );

        if (!$exists) {
            throw new NoSuchEntityException(
                __('Customer with ID "%1" does not exist.', $customerId)
            );
        }
    }

    /**
     * Get default description for transaction
     *
     * @param string $operation
     * @param float $amount
     * @return string
     */
    private function getDefaultDescription(string $operation, float $amount): string
    {
        $formattedAmount = number_format($amount, 2);
        switch ($operation) {
            case 'add':
                return "Wallet credited with {$formattedAmount}";
            case 'subtract':
                return "Wallet debited by {$formattedAmount}";
            case 'set':
                return "Wallet balance set to {$formattedAmount}";
            default:
                return "Wallet balance updated";
        }
    }
}
