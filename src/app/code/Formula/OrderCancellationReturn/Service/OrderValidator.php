<?php
namespace Formula\OrderCancellationReturn\Service;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderValidator
{
    protected $orderRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Validate order for cancellation
     *
     * @param int $customerId
     * @param int $orderId
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateCancellation($customerId, $orderId)
    {
        $order = $this->getAndValidateOrder($customerId, $orderId);
        
        $allowedStatuses = ['pending', 'processing'];
        if (!in_array($order->getStatus(), $allowedStatuses)) {
            throw new LocalizedException(
                __('Order can only be cancelled when status is pending or processing. Current status: %1', $order->getStatus())
            );
        }

        return $order;
    }

    /**
     * Validate order for return
     *
     * @param int $customerId
     * @param int $orderId
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateReturn($customerId, $orderId)
    {
        $order = $this->getAndValidateOrder($customerId, $orderId);
        
        if ($order->getStatus() !== 'complete') {
            throw new LocalizedException(
                __('Order can only be returned when status is complete. Current status: %1', $order->getStatus())
            );
        }

        return $order;
    }

    /**
     * Get and validate order for customer access and basic status
     *
     * @param int $customerId
     * @param int $orderId
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getAndValidateOrder($customerId, $orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('Order not found.'));
        }

        if ($order->getCustomerId() != $customerId) {
            throw new LocalizedException(__('You are not authorized to access this order.'));
        }

        if ($order->getState() === Order::STATE_CANCELED) {
            throw new LocalizedException(__('Order is already cancelled.'));
        }

        if ($order->getState() === Order::STATE_CLOSED) {
            throw new LocalizedException(__('Order is already closed.'));
        }

        return $order;
    }

    /**
     * Get payment method from order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return string
     */
    public function getPaymentMethod($order)
    {
        return $order->getPayment()->getMethod();
    }

    /**
     * Determine if order should be refunded based on payment method and action
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $action
     * @return bool
     */
    public function shouldRefund($order, $action)
    {
        $paymentMethod = $this->getPaymentMethod($order);
        $status = $order->getStatus();

        // For COD orders with pending status, no refund needed
        if ($paymentMethod === 'checkmo' && $status === 'pending' && $action === 'cancel') {
            return false;
        }
         // For COD orders with pending status, no refund needed
         if ($paymentMethod === 'cashondelivery' && $status === 'pending' && $action === 'cancel') {
            return false;
        }

        // All other cases need refund
        return true;
    }
}