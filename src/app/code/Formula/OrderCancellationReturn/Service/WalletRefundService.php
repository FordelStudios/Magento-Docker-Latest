<?php
namespace Formula\OrderCancellationReturn\Service;

use Formula\Wallet\Api\WalletManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class WalletRefundService
{
    protected $walletManagement;
    protected $logger;

    public function __construct(
        WalletManagementInterface $walletManagement,
        LoggerInterface $logger
    ) {
        $this->walletManagement = $walletManagement;
        $this->logger = $logger;
    }

    /**
     * Process wallet refund
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param float $amount
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processRefund($order, $amount)
    {
        try {
            $customerId = $order->getCustomerId();
            
            if (!$customerId) {
                throw new LocalizedException(__('Customer ID not found for this order.'));
            }

            // Add refund amount to customer wallet
            $result = $this->walletManagement->updateWalletBalance($customerId, $amount, 'add');
            
            if (!$result) {
                throw new LocalizedException(__('Failed to update wallet balance.'));
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
}