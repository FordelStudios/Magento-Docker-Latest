<?php
namespace Formula\OrderCancellationReturn\Service;

use Formula\Wallet\Api\WalletManagementInterface;
use Formula\Wallet\Api\WalletTransactionRepositoryInterface;
use Formula\Wallet\Api\Data\WalletTransactionInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class WalletRefundService
{
    protected $walletManagement;
    protected $transactionRepository;
    protected $customerRepository;
    protected $searchCriteriaBuilder;
    protected $logger;
    protected $registry;

    public function __construct(
        WalletManagementInterface $walletManagement,
        WalletTransactionRepositoryInterface $transactionRepository,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Registry $registry,
        LoggerInterface $logger
    ) {
        $this->walletManagement = $walletManagement;
        $this->transactionRepository = $transactionRepository;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * Process wallet refund
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param float $amount
     * @param string|null $referenceType
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processRefund($order, $amount, $referenceType = null)
    {
        try {
            $customerId = $order->getCustomerId();

            if (!$customerId) {
                throw new LocalizedException(__('Customer ID not found for this order.'));
            }

            // Idempotency: Check if a refund transaction already exists for this order
            if ($referenceType && $this->hasExistingRefundTransaction($customerId, $order->getId(), $referenceType)) {
                $this->logger->info('Wallet refund already processed for order', [
                    'order_id' => $order->getIncrementId(),
                    'reference_type' => $referenceType,
                ]);
                return [
                    'success' => true,
                    'transaction_id' => 'already_refunded_wallet_' . $order->getIncrementId(),
                    'refund_amount' => $amount,
                    'refund_method' => 'wallet'
                ];
            }

            // Get current balance before update
            $currentBalance = $this->walletManagement->getWalletBalance($customerId);

            // Get customer and update wallet balance directly
            $customer = $this->customerRepository->getById($customerId);
            $newBalance = $currentBalance + $amount;
            $customer->setCustomAttribute('wallet_balance', $newBalance);

            // Set registry flag to allow wallet balance update
            $this->registry->register('wallet_balance_update_in_progress', true, true);

            $this->customerRepository->save($customer);

            // Unregister the flag
            $this->registry->unregister('wallet_balance_update_in_progress');

            // Log transaction with proper reference type and order ID
            if ($referenceType) {
                $description = $this->getRefundDescription($order, $referenceType);

                try {
                    $this->transactionRepository->createTransaction(
                        $customerId,
                        $amount,
                        WalletTransactionInterface::TYPE_CREDIT,
                        $currentBalance,
                        $newBalance,
                        $description,
                        $referenceType,
                        $order->getId()
                    );
                } catch (\Exception $transactionError) {
                    $this->logger->error('Error logging wallet refund transaction', [
                        'order_id' => $order->getId(),
                        'error' => $transactionError->getMessage()
                    ]);
                }
            }

            return [
                'success' => true,
                'transaction_id' => 'wallet_refund_' . $order->getIncrementId() . '_' . time(),
                'refund_amount' => $amount,
                'refund_method' => 'wallet'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Wallet refund failed: ' . $e->getMessage());
            throw new LocalizedException(__('Wallet refund failed: %1', $e->getMessage()));
        }
    }

    /**
     * Check if a wallet refund transaction already exists for this order
     *
     * @param int $customerId
     * @param int $orderId
     * @param string $referenceType
     * @return bool
     */
    protected function hasExistingRefundTransaction($customerId, $orderId, $referenceType)
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('customer_id', $customerId)
                ->addFilter('reference_type', $referenceType)
                ->addFilter('reference_id', $orderId)
                ->addFilter('type', WalletTransactionInterface::TYPE_CREDIT)
                ->setPageSize(1)
                ->create();

            $results = $this->transactionRepository->getList($searchCriteria);
            return $results->getTotalCount() > 0;
        } catch (\Exception $e) {
            // If we can't check, proceed with refund (fail-open for customer benefit)
            $this->logger->warning('Could not check existing wallet refund transactions', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
        return false;
    }

    protected function getRefundDescription($order, $referenceType)
    {
        $orderNumber = $order->getIncrementId();

        switch ($referenceType) {
            case WalletTransactionInterface::REFERENCE_TYPE_ORDER_CANCEL:
                return "Refund for cancelled order #{$orderNumber}";

            case WalletTransactionInterface::REFERENCE_TYPE_ORDER_RETURN:
                return "Refund for returned order #{$orderNumber}";

            default:
                return "Refund for order #{$orderNumber}";
        }
    }
}