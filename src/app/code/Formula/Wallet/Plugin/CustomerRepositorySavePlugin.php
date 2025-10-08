<?php
namespace Formula\Wallet\Plugin;

use Formula\Wallet\Api\Data\WalletTransactionInterface;
use Formula\Wallet\Api\WalletTransactionRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class CustomerRepositorySavePlugin
{
    /**
     * @var WalletTransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param WalletTransactionRepositoryInterface $transactionRepository
     * @param State $appState
     * @param LoggerInterface $logger
     */
    public function __construct(
        WalletTransactionRepositoryInterface $transactionRepository,
        State $appState,
        LoggerInterface $logger
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->appState = $appState;
        $this->logger = $logger;
    }

    /**
     * Log wallet transaction when customer balance changes
     *
     * @param CustomerRepositoryInterface $subject
     * @param callable $proceed
     * @param CustomerInterface $customer
     * @param string|null $passwordHash
     * @return CustomerInterface
     */
    public function aroundSave(
        CustomerRepositoryInterface $subject,
        callable $proceed,
        CustomerInterface $customer,
        $passwordHash = null
    ) {
        // Only interfere with save for admin area updates
        // For frontend/API saves (like order placement), pass through directly
        if (!$this->shouldLogTransaction()) {
            return $proceed($customer, $passwordHash);
        }

        // Admin area - track balance changes for transaction logging
        $customerId = $customer->getId();
        $oldBalance = 0;

        // Get old balance if customer exists
        if ($customerId) {
            try {
                $existingCustomer = $subject->getById($customerId);
                if ($existingCustomer->getCustomAttribute('wallet_balance')) {
                    $oldBalance = (float)$existingCustomer->getCustomAttribute('wallet_balance')->getValue();
                }
            } catch (\Exception $e) {
                $this->logger->error('Error fetching existing wallet balance', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Perform the actual save
        $result = $proceed($customer, $passwordHash);

        // Get new balance after save
        $newBalance = 0;
        if ($result->getCustomAttribute('wallet_balance')) {
            $newBalance = (float)$result->getCustomAttribute('wallet_balance')->getValue();
        }

        // If balance changed, log transaction
        if ($oldBalance != $newBalance && $result->getId()) {
            try {
                $balanceDifference = $newBalance - $oldBalance;
                $transactionType = $balanceDifference > 0 ? WalletTransactionInterface::TYPE_CREDIT : WalletTransactionInterface::TYPE_DEBIT;
                $transactionAmount = abs($balanceDifference);

                // Determine reference type based on area
                $referenceType = $this->getReferenceType();

                $this->transactionRepository->createTransaction(
                    $result->getId(),
                    $transactionAmount,
                    $transactionType,
                    $oldBalance,
                    $newBalance,
                    $this->getTransactionDescription($transactionType, $referenceType),
                    $referenceType,
                    null
                );

                $this->logger->info('Wallet transaction logged via plugin', [
                    'customer_id' => $result->getId(),
                    'type' => $transactionType,
                    'amount' => $transactionAmount,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'reference_type' => $referenceType
                ]);

            } catch (LocalizedException $e) {
                $this->logger->error('Error logging wallet transaction', [
                    'customer_id' => $result->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Check if transaction should be logged by this plugin
     * Only log for admin area changes (panel or API)
     *
     * @return bool
     */
    protected function shouldLogTransaction()
    {
        try {
            $areaCode = $this->appState->getAreaCode();
            // Only log for admin areas
            return $areaCode === 'adminhtml' || $areaCode === 'webapi_rest' || $areaCode === 'webapi_soap';
        } catch (\Exception $e) {
            $this->logger->error('Error determining area code: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reference type based on current area
     *
     * @return string
     */
    protected function getReferenceType()
    {
        try {
            $areaCode = $this->appState->getAreaCode();
            if ($areaCode === 'adminhtml') {
                return WalletTransactionInterface::REFERENCE_TYPE_ADMIN_PANEL;
            } elseif ($areaCode === 'webapi_rest' || $areaCode === 'webapi_soap') {
                return WalletTransactionInterface::REFERENCE_TYPE_ADMIN_API;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error determining area code: ' . $e->getMessage());
        }

        return WalletTransactionInterface::REFERENCE_TYPE_ADMIN_PANEL;
    }

    /**
     * Get transaction description
     *
     * @param string $type
     * @param string $referenceType
     * @return string
     */
    protected function getTransactionDescription($type, $referenceType)
    {
        $action = $type === WalletTransactionInterface::TYPE_CREDIT ? 'credited' : 'debited';

        if ($referenceType === WalletTransactionInterface::REFERENCE_TYPE_ADMIN_PANEL) {
            return "Admin adjustment - wallet {$action} via admin panel";
        } elseif ($referenceType === WalletTransactionInterface::REFERENCE_TYPE_ADMIN_API) {
            return "Admin adjustment - wallet {$action} via API";
        }

        return "Wallet {$action}";
    }
}
