<?php
namespace Formula\OrderCancellationReturn\Service;

use Formula\OrderCancellationReturn\Service\RazorpayRefundService;
use Formula\OrderCancellationReturn\Service\WalletRefundService;
use Formula\OrderCancellationReturn\Service\OrderValidator;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class RefundProcessor
{
    protected $razorpayRefundService;
    protected $walletRefundService;
    protected $orderValidator;
    protected $logger;

    public function __construct(
        RazorpayRefundService $razorpayRefundService,
        WalletRefundService $walletRefundService,
        OrderValidator $orderValidator,
        LoggerInterface $logger
    ) {
        $this->razorpayRefundService = $razorpayRefundService;
        $this->walletRefundService = $walletRefundService;
        $this->orderValidator = $orderValidator;
        $this->logger = $logger;
    }

    /**
     * Process refund based on payment method and order conditions
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $action
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processRefund($order, $action)
    {
        $paymentMethod = $this->orderValidator->getPaymentMethod($order);
        $grandTotal = $order->getGrandTotal();
        $walletAmountUsed = $order->getWalletAmountUsed() ?: 0;

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
                return $this->processMixedPaymentRefund($order, $paymentMethod, $grandTotal, $walletAmountUsed, $referenceType);
            } else {
                // Single payment method
                return $this->processSinglePaymentRefund($order, $paymentMethod, $grandTotal, $referenceType);
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
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processSinglePaymentRefund($order, $paymentMethod, $amount, $referenceType = null)
    {
        switch ($paymentMethod) {
            case 'razorpay':
                $result = $this->razorpayRefundService->processRefund($order, $amount);
                if (strpos($result['transaction_id'], 'already_refunded_') === 0) {
                    $result['status_message'] = sprintf('₹%.2f was already refunded to Razorpay', $amount);
                } else {
                    $result['status_message'] = sprintf('Refunded ₹%.2f to Razorpay (Transaction: %s)', $amount, $result['transaction_id']);
                }
                return $result;

            case 'checkmo': // Cash on Delivery
            case 'cashondelivery':
            case 'walletpayment':
                $result = $this->walletRefundService->processRefund($order, $amount, $referenceType);
                $result['status_message'] = sprintf('Refunded ₹%.2f to customer wallet', $amount);
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
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processMixedPaymentRefund($order, $paymentMethod, $grandTotal, $walletAmountUsed, $referenceType = null)
    {
        $otherPaymentAmount = $grandTotal - $walletAmountUsed;
        $results = [];

        // Refund wallet portion to wallet
        if ($walletAmountUsed > 0) {
            $walletResult = $this->walletRefundService->processRefund($order, $walletAmountUsed, $referenceType);
            $results[] = $walletResult;
        }

        // Refund other payment method portion
        if ($otherPaymentAmount > 0) {
            switch ($paymentMethod) {
                case 'razorpay':
                    $otherResult = $this->razorpayRefundService->processRefund($order, $otherPaymentAmount);
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
        $statusMessages = [];
        foreach ($results as $result) {
            if ($result['refund_method'] === 'razorpay') {
                if (strpos($result['transaction_id'], 'already_refunded_') === 0) {
                    $statusMessages[] = sprintf('₹%.2f was already refunded to Razorpay', $result['refund_amount']);
                } else {
                    $statusMessages[] = sprintf('₹%.2f to Razorpay (Transaction: %s)', $result['refund_amount'], $result['transaction_id']);
                }
            } elseif ($result['refund_method'] === 'wallet') {
                $statusMessages[] = sprintf('₹%.2f to wallet', $result['refund_amount']);
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