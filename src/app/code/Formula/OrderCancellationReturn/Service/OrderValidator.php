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
     * SECURITY FIX: COD orders can only be cancelled when pending (before shipment).
     * Once in processing, the shipment is in transit and cancellation could be exploited
     * to get wallet credit for money that was never collected.
     *
     * @param int $customerId
     * @param int $orderId
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateCancellation($customerId, $orderId)
    {
        $order = $this->getAndValidateOrder($customerId, $orderId);
        $paymentMethod = $this->getPaymentMethod($order);

        // COD orders: Only allow cancellation in pending status
        // Once 'processing', the shipment may be in transit and we can't verify payment
        if ($this->isCodPayment($paymentMethod)) {
            if ($order->getStatus() !== 'pending') {
                throw new LocalizedException(
                    __('COD orders can only be cancelled when status is pending. ' .
                       'Current status: %1. Please contact customer support for assistance.',
                       $order->getStatus())
                );
            }
        } else {
            // Prepaid orders (Razorpay, Wallet) can be cancelled before shipment is picked up
            // Once shipped/in_transit, customer must use return flow instead
            $allowedStatuses = [
                'pending',
                'processing',
                'shipment_pending_manual',  // Shiprocket shipment creation failed
                'shipment_created',          // Shipment created but not picked up
                'pickup_scheduled'           // Pickup scheduled but not yet picked up
            ];
            if (!in_array($order->getStatus(), $allowedStatuses)) {
                throw new LocalizedException(
                    __('Order can only be cancelled before shipment pickup. ' .
                       'Current status: %1. Please use Return option instead.', $order->getStatus())
                );
            }
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
     * SECURITY FIX: COD orders should NEVER get wallet refund on cancellation because
     * the money was never collected. For returns, only refund if the order was
     * actually delivered (customer paid the delivery person).
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param string $action 'cancel' or 'return'
     * @return bool
     */
    public function shouldRefund($order, $action)
    {
        $paymentMethod = $this->getPaymentMethod($order);
        $status = $order->getStatus();

        // COD orders: Special handling to prevent free money exploits
        if ($this->isCodPayment($paymentMethod)) {
            // COD cancellation: NEVER refund (money was never collected)
            if ($action === 'cancel') {
                return false;
            }

            // COD return: Only refund if order was actually delivered (customer paid)
            // 'complete' or 'delivered' status indicates customer received and paid
            if ($action === 'return') {
                $deliveredStatuses = ['complete', 'delivered'];
                return in_array($status, $deliveredStatuses);
            }

            // Default: no refund for COD
            return false;
        }

        // Prepaid orders (Razorpay, Wallet): Always eligible for refund
        // because customer has already paid
        return true;
    }

    /**
     * Check if payment method is Cash on Delivery
     *
     * @param string $paymentMethod
     * @return bool
     */
    public function isCodPayment($paymentMethod)
    {
        $codMethods = ['cashondelivery', 'checkmo'];
        return in_array($paymentMethod, $codMethods);
    }

    /**
     * Check if payment method is prepaid (Razorpay or Wallet)
     *
     * @param string $paymentMethod
     * @return bool
     */
    public function isPrepaidPayment($paymentMethod)
    {
        $prepaidMethods = ['razorpay', 'walletpayment'];
        return in_array($paymentMethod, $prepaidMethods);
    }
}