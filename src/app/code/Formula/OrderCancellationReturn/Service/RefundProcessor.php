<?php
namespace Formula\OrderCancellationReturn\Service;

use Formula\OrderCancellationReturn\Service\RazorpayRefundService;
use Formula\OrderCancellationReturn\Service\WalletRefundService;
use Formula\OrderCancellationReturn\Service\OrderValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Psr\Log\LoggerInterface;

class RefundProcessor
{
    protected $razorpayRefundService;
    protected $walletRefundService;
    protected $orderValidator;
    protected $logger;
    protected $priceCurrency;

    public function __construct(
        RazorpayRefundService $razorpayRefundService,
        WalletRefundService $walletRefundService,
        OrderValidator $orderValidator,
        LoggerInterface $logger,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->razorpayRefundService = $razorpayRefundService;
        $this->walletRefundService = $walletRefundService;
        $this->orderValidator = $orderValidator;
        $this->logger = $logger;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Format amount with proper currency symbol
     *
     * @param float $amount
     * @param int|null $storeId
     * @return string
     */
    protected function formatCurrency($amount, $storeId = null)
    {
        return $this->priceCurrency->format($amount, false, 2, $storeId);
    }

    /**
     * Process refund based on payment method and order conditions
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $action
     * @param string $refundTarget 'wallet' routes to Formula Wallet; 'source' routes back to Razorpay. Default 'wallet'.
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processRefund($order, $action, $refundTarget = 'wallet')
    {
        $paymentMethod = $this->orderValidator->getPaymentMethod($order);
        $grandTotal = $order->getGrandTotal();
        $walletAmountUsed = $order->getWalletAmountUsed() ?: 0;

        // Normalise — any unexpected value falls back to wallet
        $resolvedTarget = ($refundTarget === 'source') ? 'source' : 'wallet';

        // Determine reference type based on action
        $referenceType = $this->getReferenceType($action);

        // Check if refund is needed
        if (!$this->orderValidator->shouldRefund($order, $action)) {
            return [
                'success' => true,
                'transaction_id' => 'no_refund_needed',
                'refund_amount' => 0,
                'refund_method' => 'none',
                'message' => 'No refund needed for pending COD order',
                'status_message' => 'No refund processed (COD order not yet delivered)'
            ];
        }

        // Handle different payment scenarios
        try {
            if ($walletAmountUsed > 0) {
                // Mixed payment or wallet-only payment
                return $this->processMixedPaymentRefund($order, $paymentMethod, $grandTotal, $walletAmountUsed, $referenceType, $resolvedTarget);
            } else {
                // Single payment method
                return $this->processSinglePaymentRefund($order, $paymentMethod, $grandTotal, $referenceType, $resolvedTarget);
            }
        } catch (\Exception $e) {
            $this->logger->error('Refund processing failed: ' . $e->getMessage());
            throw new LocalizedException(__('Refund processing failed: %1', $e->getMessage()));
        }
    }

    /**
     * Get reference type based on action
     *
     * @param string $action
     * @return string
     */
    protected function getReferenceType($action)
    {
        switch ($action) {
            case 'cancel':
                return \Formula\Wallet\Api\Data\WalletTransactionInterface::REFERENCE_TYPE_ORDER_CANCEL;
            case 'return':
                return \Formula\Wallet\Api\Data\WalletTransactionInterface::REFERENCE_TYPE_ORDER_RETURN;
            default:
                return null;
        }
    }

    /**
     * Process refund for single payment method
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $paymentMethod
     * @param float $amount
     * @param string|null $referenceType
     * @param string $refundTarget 'wallet' or 'source'
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processSinglePaymentRefund($order, $paymentMethod, $amount, $referenceType = null, $refundTarget = 'wallet')
    {
        switch ($paymentMethod) {
            case 'razorpay':
                // Honour customer's choice: wallet shortcircuits the Razorpay API call
                if ($refundTarget === 'wallet') {
                    $result = $this->walletRefundService->processRefund($order, $amount, $referenceType);
                    $formattedAmount = $this->formatCurrency($amount, $order->getStoreId());
                    $result['status_message'] = sprintf('Refunded %s to customer wallet (Razorpay order, wallet preference)', $formattedAmount);
                    return $result;
                }
                $result = $this->razorpayRefundService->processRefund($order, $amount);
                $formattedAmount = $this->formatCurrency($amount, $order->getStoreId());
                if (strpos($result['transaction_id'], 'already_refunded_') === 0) {
                    $result['status_message'] = sprintf('%s was already refunded to Razorpay', $formattedAmount);
                } else {
                    $result['status_message'] = sprintf('Refunded %s to Razorpay (Transaction: %s)', $formattedAmount, $result['transaction_id']);
                }
                return $result;

            case 'checkmo': // Cash on Delivery
            case 'cashondelivery':
            case 'walletpayment':
                $result = $this->walletRefundService->processRefund($order, $amount, $referenceType);
                $formattedAmount = $this->formatCurrency($amount, $order->getStoreId());
                $result['status_message'] = sprintf('Refunded %s to customer wallet', $formattedAmount);
                return $result;

            default:
                throw new LocalizedException(__('Unsupported payment method: %1', $paymentMethod));
        }
    }

    /**
     * Process refund for mixed payment methods
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $paymentMethod
     * @param float $grandTotal
     * @param float $walletAmountUsed
     * @param string|null $referenceType
     * @param string $refundTarget 'wallet' or 'source'
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processMixedPaymentRefund($order, $paymentMethod, $grandTotal, $walletAmountUsed, $referenceType = null, $refundTarget = 'wallet')
    {
        $otherPaymentAmount = $grandTotal - $walletAmountUsed;
        $results = [];

        // Refund wallet portion to wallet (always — this portion came from wallet)
        if ($walletAmountUsed > 0) {
            $walletResult = $this->walletRefundService->processRefund($order, $walletAmountUsed, $referenceType);
            $results[] = $walletResult;
        }

        // Refund other payment method portion — honour $refundTarget for Razorpay
        if ($otherPaymentAmount > 0) {
            switch ($paymentMethod) {
                case 'razorpay':
                    // Decision: if user picks wallet, send entire order amount to wallet (both portions)
                    if ($refundTarget === 'wallet') {
                        $otherResult = $this->walletRefundService->processRefund($order, $otherPaymentAmount, $referenceType);
                    } else {
                        $otherResult = $this->razorpayRefundService->processRefund($order, $otherPaymentAmount);
                    }
                    break;

                case 'checkmo': // Cash on Delivery
                case 'cashondelivery':
                    $otherResult = $this->walletRefundService->processRefund($order, $otherPaymentAmount, $referenceType);
                    break;

                default:
                    throw new LocalizedException(__('Unsupported payment method: %1', $paymentMethod));
            }
            $results[] = $otherResult;
        }

        // Combine results
        $totalRefundAmount = array_sum(array_column($results, 'refund_amount'));
        $transactionIds = array_column($results, 'transaction_id');
        $refundMethods = array_unique(array_column($results, 'refund_method'));
        
        // Create detailed status message for mixed payments
        $storeId = $order->getStoreId();
        $statusMessages = [];
        foreach ($results as $result) {
            $formattedAmount = $this->formatCurrency($result['refund_amount'], $storeId);
            if ($result['refund_method'] === 'razorpay') {
                if (strpos($result['transaction_id'], 'already_refunded_') === 0) {
                    $statusMessages[] = sprintf('%s was already refunded to Razorpay', $formattedAmount);
                } else {
                    $statusMessages[] = sprintf('%s to Razorpay (Transaction: %s)', $formattedAmount, $result['transaction_id']);
                }
            } elseif ($result['refund_method'] === 'wallet') {
                $statusMessages[] = sprintf('%s to wallet', $formattedAmount);
            }
        }
        $combinedStatusMessage = 'Refunded ' . implode(', ', $statusMessages);

        return [
            'success' => true,
            'transaction_id' => implode(', ', $transactionIds),
            'refund_amount' => $totalRefundAmount,
            'refund_method' => implode('+', $refundMethods),
            'mixed_payment' => true,
            'refund_details' => $results,
            'status_message' => $combinedStatusMessage
        ];
    }
}