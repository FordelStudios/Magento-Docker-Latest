<?php
namespace Formula\OrderCancellationReturn\Model;

use Formula\OrderCancellationReturn\Api\OrderReturnInterface;
use Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface;
use Formula\OrderCancellationReturn\Api\Data\RefundResponseInterfaceFactory;
use Formula\OrderCancellationReturn\Service\OrderValidator;
use Formula\OrderCancellationReturn\Service\RefundProcessor;
use Formula\Shiprocket\Service\ShiprocketReturnService;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
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

    public function __construct(
        OrderValidator $orderValidator,
        RefundProcessor $refundProcessor,
        ObjectManagerInterface $objectManager,
        OrderRepositoryInterface $orderRepository,
        RefundResponseInterfaceFactory $refundResponseFactory,
        LoggerInterface $logger
    ) {
        $this->orderValidator = $orderValidator;
        $this->refundProcessor = $refundProcessor;
        $this->objectManager = $objectManager;
        $this->orderRepository = $orderRepository;
        $this->refundResponseFactory = $refundResponseFactory;
        $this->logger = $logger;
    }

    /**
     * Return an order for a customer
     *
     * @param int $customerId Customer ID
     * @param int $orderId Order ID to return
     * @param string|null $reason Optional reason for return
     * @return \Formula\OrderCancellationReturn\Api\Data\RefundResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function returnOrder($customerId, $orderId, $reason = null)
    {
        $response = $this->refundResponseFactory->create();

        try {
            // Validate order for return
            $order = $this->orderValidator->validateReturn($customerId, $orderId);

            // Step 1: Change status to return_requested
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus('return_requested');
            
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

            // Step 3: Process refund (immediate for better customer experience)
            $refundResult = $this->refundProcessor->processRefund($order, 'return');
            
            // Add refund information to status history
            $refundMessage = '';
            if (isset($refundResult['status_message'])) {
                $refundMessage = $refundResult['status_message'];
            } else {
                $refundMessage = 'Refund method: ' . $refundResult['refund_method'] . '. Refund amount: ' . $refundResult['refund_amount'];
            }
            
            $order->addStatusHistoryComment('Return refund processed. ' . $refundMessage, $order->getStatus());

            // NOTE: Inventory will be restored only when product is physically received
            // This will be handled by the webhook when return status becomes 'return_received'

            $this->orderRepository->save($order);

            // Build successful response
            $response->setSuccess(true);
            $response->setError(false);
            $response->setOrderId($order->getId());
            $response->setIncrementId($order->getIncrementId());
            $response->setRefundAmount($refundResult['refund_amount']);
            $response->setRefundMethod($refundResult['refund_method']);
            $response->setTransactionId($refundResult['transaction_id']);
            $response->setMessage('Order returned successfully and refund processed.');

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
}