<?php
namespace Formula\OrderCancellationReturn\Model;

use Formula\OrderCancellationReturn\Api\OrderReturnInterface;
use Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface;
use Formula\OrderCancellationReturn\Api\Data\RefundResponseInterfaceFactory;
use Formula\OrderCancellationReturn\Service\OrderValidator;
use Formula\OrderCancellationReturn\Service\RefundProcessor;
use Formula\OrderCancellationReturn\Service\ReturnEmailSender;
use Formula\Shiprocket\Service\ShiprocketReturnService;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Psr\Log\LoggerInterface;

class OrderReturn implements OrderReturnInterface
{
    protected $orderValidator;
    protected $refundProcessor;
    protected $shiprocketReturnService;
    protected $orderRepository;
    protected $refundResponseFactory;
    protected $logger;
    protected $objectManager;
    protected $priceCurrency;
    protected $returnEmailSender;

    public function __construct(
        OrderValidator $orderValidator,
        RefundProcessor $refundProcessor,
        ObjectManagerInterface $objectManager,
        OrderRepositoryInterface $orderRepository,
        RefundResponseInterfaceFactory $refundResponseFactory,
        LoggerInterface $logger,
        PriceCurrencyInterface $priceCurrency,
        ReturnEmailSender $returnEmailSender
    ) {
        $this->orderValidator = $orderValidator;
        $this->refundProcessor = $refundProcessor;
        $this->objectManager = $objectManager;
        $this->orderRepository = $orderRepository;
        $this->refundResponseFactory = $refundResponseFactory;
        $this->logger = $logger;
        $this->priceCurrency = $priceCurrency;
        $this->returnEmailSender = $returnEmailSender;
    }

    /**
     * Return an order for a customer
     *
     * @param int $customerId Customer ID
     * @param int $orderId Order ID to return
     * @param string|null $reason Optional reason for return
     * @param string[]|null $images Optional array of image paths (from upload endpoint)
     * @param int|null $pickupAddressId Optional pickup address ID
     * @return \Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function returnOrder($customerId, $orderId, $reason = null, $images = null, $pickupAddressId = null)
    {
        $response = $this->refundResponseFactory->create();

        try {
            // Validate order for return
            $order = $this->orderValidator->validateReturn($customerId, $orderId);

            // Step 1: Change status to return_requested
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus('return_requested');

            // Store return request details
            if ($reason) {
                $order->setData('return_reason', $reason);
            }
            if ($images && is_array($images)) {
                $order->setData('return_images', json_encode($images));
            }
            if ($pickupAddressId) {
                $order->setData('return_pickup_address_id', $pickupAddressId);
            }

            $returnReason = $reason ? ': ' . $reason : '';
            $order->addStatusHistoryComment(
                'Return requested by customer' . $returnReason,
                'return_requested'
            );
            $this->orderRepository->save($order);

            // Step 2: Schedule Shiprocket return pickup
            try {
                $shiprocketReturnService = $this->getShiprocketReturnService();
                $shiprocketResponse = $shiprocketReturnService->createReturnPickup($order, $reason);
                
                if ($shiprocketResponse['success']) {
                    // Update status to return_pickup_scheduled
                    $order->setStatus('return_pickup_scheduled');
                    
                    $pickupMessage = 'Shiprocket return pickup scheduled';
                    if (isset($shiprocketResponse['pickup_id'])) {
                        $pickupMessage .= ' (Pickup ID: ' . $shiprocketResponse['pickup_id'] . ')';
                    }
                    
                    $order->addStatusHistoryComment($pickupMessage, 'return_pickup_scheduled');
                    
                    // Store Shiprocket return data in order
                    $order->setData('shiprocket_return_id', $shiprocketResponse['return_id'] ?? null);
                    $order->setData('shiprocket_pickup_id', $shiprocketResponse['pickup_id'] ?? null);
                }
            } catch (\Exception $e) {
                // If Shiprocket fails, continue with manual process
                $this->logger->warning('Shiprocket return pickup failed: ' . $e->getMessage());
                $order->addStatusHistoryComment(
                    'Shiprocket return pickup failed. Processing manual return: ' . $e->getMessage(),
                    'return_requested'
                );
            }

            // SECURITY FIX: DO NOT process refund immediately
            // Refund will be processed via Shiprocket webhook when return is received
            // This prevents customers from getting refund without actually returning products

            $paymentMethod = $this->orderValidator->getPaymentMethod($order);
            $grandTotal = $order->getGrandTotal();
            $walletAmountUsed = $order->getWalletAmountUsed() ?: 0;

            // Store pending refund details for later processing
            $order->setData('pending_return_refund_amount', $grandTotal);
            $order->setData('pending_return_refund_method', $paymentMethod);
            $order->setData('pending_return_wallet_amount', $walletAmountUsed);
            $order->setData('pending_return_requested_at', date('Y-m-d H:i:s'));

            // Add informative status history
            $refundMessage = sprintf(
                'Return initiated. Refund of %s will be processed after product is received and inspected.',
                $this->formatCurrency($grandTotal)
            );
            $order->addStatusHistoryComment($refundMessage, $order->getStatus());

            // NOTE: Inventory will be restored only when product is physically received
            // This will be handled by the Shiprocket webhook when return status becomes 'return_received'

            $this->orderRepository->save($order);

            // Send confirmation email
            $this->returnEmailSender->sendReturnRequestedEmail($order, $reason ?? '');

            // Build successful response - refund is PENDING, not processed
            $response->setSuccess(true);
            $response->setError(false);
            $response->setOrderId($order->getId());
            $response->setIncrementId($order->getIncrementId());
            $response->setRefundAmount(0);  // No immediate refund
            $response->setRefundMethod('pending');
            $response->setTransactionId('return_pending_' . $order->getIncrementId() . '_' . time());
            $response->setMessage(
                'Return request submitted successfully. ' .
                'Refund will be processed within 5-7 business days after we receive and inspect the returned items.'
            );

        } catch (\Exception $e) {
            $this->logger->error('Order return failed: ' . $e->getMessage());
            
            $response->setSuccess(false);
            $response->setError(true);
            $response->setMessage($e->getMessage());
        }

        return $response;
    }

    /**
     * Get ShiprocketReturnService instance (lazy loading)
     *
     * @return ShiprocketReturnService
     */
    private function getShiprocketReturnService()
    {
        if (!$this->shiprocketReturnService) {
            $this->shiprocketReturnService = $this->objectManager->get(ShiprocketReturnService::class);
        }
        return $this->shiprocketReturnService;
    }

    /**
     * Format currency amount with proper symbol
     *
     * @param float $amount
     * @param int|null $storeId
     * @return string
     */
    private function formatCurrency($amount, $storeId = null)
    {
        return $this->priceCurrency->format($amount, false, 2, $storeId);
    }
}