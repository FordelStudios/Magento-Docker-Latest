<?php
namespace Formula\OrderCancellationReturn\Service;

use Formula\Wallet\Api\WalletManagementInterface;
use Formula\Wallet\Api\WalletTransactionRepositoryInterface;
use Formula\Wallet\Api\Data\WalletTransactionInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class WalletRefundService
{
    protected $walletManagement;
    protected $transactionRepository;
    protected $customerRepository;
    protected $logger;
    protected $registry;

    public function __construct(
        WalletManagementInterface $walletManagement,
        WalletTransactionRepositoryInterface $transactionRepository,
        CustomerRepositoryInterface $customerRepository,
        Registry $registry,
        LoggerInterface $logger
    ) {
        $this->walletManagement = $walletManagement;
        $this->transactionRepository = $transactionRepository;
        $this->customerRepository = $customerRepository;
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
     * Get refund description based on reference type
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $referenceType
     * @return string
     */
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