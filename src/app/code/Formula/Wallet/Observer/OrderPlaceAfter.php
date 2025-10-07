<?php
namespace Formula\Wallet\Observer;

use Formula\Wallet\Api\Data\WalletTransactionInterface;
use Formula\Wallet\Api\WalletTransactionRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class OrderPlaceAfter implements ObserverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var WalletTransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     * @param WalletTransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        WalletTransactionRepositoryInterface $transactionRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Deduct wallet amount from customer balance after order placement
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        
        $customerId = $order->getCustomerId();
        $walletAmountUsed = $order->getWalletAmountUsed();

        if (!$customerId || !$walletAmountUsed || $walletAmountUsed <= 0) {
            return;
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $currentWalletBalance = 0;
            
            if ($customer->getCustomAttribute('wallet_balance')) {
                $currentWalletBalance = (float)$customer->getCustomAttribute('wallet_balance')->getValue();
            }

            $newBalance = max(0, $currentWalletBalance - $walletAmountUsed);
            $customer->setCustomAttribute('wallet_balance', $newBalance);
            
            $this->customerRepository->save($customer);

            // Add status history entry for wallet payment
            $order->addStatusHistoryComment(
                __('Wallet payment processed: $%1 paid from customer wallet.', number_format($walletAmountUsed, 2)),
                false // Don't change order status
            )->setIsCustomerNotified(false);
            
            $this->orderRepository->save($order);

            $this->logger->info('Wallet amount deducted for order', [
                'order_id' => $order->getId(),
                'customer_id' => $customerId,
                'wallet_amount_used' => $walletAmountUsed,
                'old_balance' => $currentWalletBalance,
                'new_balance' => $newBalance
            ]);

            // Log transaction
            try {
                $this->transactionRepository->createTransaction(
                    $customerId,
                    $walletAmountUsed,
                    WalletTransactionInterface::TYPE_DEBIT,
                    $currentWalletBalance,
                    $newBalance,
                    'Wallet payment for order #' . $order->getIncrementId(),
                    WalletTransactionInterface::REFERENCE_TYPE_ORDER,
                    $order->getId()
                );
            } catch (\Exception $transactionError) {
                $this->logger->error('Error logging wallet transaction for order', [
                    'order_id' => $order->getId(),
                    'error' => $transactionError->getMessage()
                ]);
            }

        } catch (LocalizedException $e) {
            $this->logger->error('Error updating customer wallet balance after order placement', [
                'order_id' => $order->getId(),
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error updating customer wallet balance', [
                'order_id' => $order->getId(),
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
        }
    }
}