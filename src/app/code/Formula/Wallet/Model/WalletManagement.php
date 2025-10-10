<?php
namespace Formula\Wallet\Model;

use Formula\Wallet\Api\WalletManagementInterface;
use Formula\Wallet\Api\Data\WalletBalanceInterface;
use Formula\Wallet\Api\Data\WalletBalanceInterfaceFactory;
use Formula\Wallet\Api\WalletTransactionRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Psr\Log\LoggerInterface;
use Formula\RazorpayApi\Api\Data\OrderResponseInterfaceFactory;
use Magento\Sales\Model\Order;

class WalletManagement implements WalletManagementInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var CartManagementInterface
     */
    protected $cartManagement;

    /**
     * @var WalletBalanceInterfaceFactory
     */
    protected $walletBalanceFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var WalletTransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    protected $sortOrderBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ShiprocketShipmentService
     */
    protected $shiprocketShipmentService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OrderResponseInterfaceFactory
     */
    protected $orderResponseFactory;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param CartManagementInterface $cartManagement
     * @param WalletBalanceInterfaceFactory $walletBalanceFactory
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param WalletTransactionRepositoryInterface $transactionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param ShiprocketShipmentService $shiprocketShipmentService
     * @param LoggerInterface $logger
     * @param OrderResponseInterfaceFactory $orderResponseFactory
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CartRepositoryInterface $quoteRepository,
        CartManagementInterface $cartManagement,
        WalletBalanceInterfaceFactory $walletBalanceFactory,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        WalletTransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        OrderRepositoryInterface $orderRepository,
        ShiprocketShipmentService $shiprocketShipmentService,
        LoggerInterface $logger,
        OrderResponseInterfaceFactory $orderResponseFactory
    ) {
        $this->customerRepository = $customerRepository;
        $this->quoteRepository = $quoteRepository;
        $this->cartManagement = $cartManagement;
        $this->walletBalanceFactory = $walletBalanceFactory;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->orderRepository = $orderRepository;
        $this->shiprocketShipmentService = $shiprocketShipmentService;
        $this->logger = $logger;
        $this->orderResponseFactory = $orderResponseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getWalletBalance($customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $walletBalance = $customer->getCustomAttribute('wallet_balance');
            return $walletBalance ? (float)$walletBalance->getValue() : 0.00;
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Customer with ID "%1" does not exist.', $customerId));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyWalletToCart($customerId, $cartId, $amount = null)
    {
        try {
            $quote = $this->quoteRepository->get($cartId);
            
            if ($quote->getCustomerId() != $customerId) {
                throw new LocalizedException(__('You are not authorized to access this cart.'));
            }

            $walletBalance = $this->getWalletBalance($customerId);
            
            if ($walletBalance <= 0) {
                throw new LocalizedException(__('Insufficient wallet balance.'));
            }

            $grandTotal = $quote->getGrandTotal();
            
            if (!$amount) {
                $amount = min($walletBalance, $grandTotal);
            } else {
                if ($amount > $walletBalance) {
                    throw new LocalizedException(__('Wallet balance is insufficient for the requested amount.'));
                }
                if ($amount > $grandTotal) {
                    $amount = $grandTotal;
                }
            }

            $quote->setWalletAmountUsed($amount);
            $quote->setBaseWalletAmountUsed($amount);
            
            $this->quoteRepository->save($quote);
            $quote->collectTotals();
            $this->quoteRepository->save($quote);

            return true;
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Cart with ID "%1" does not exist.', $cartId));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeWalletFromCart($customerId, $cartId)
    {
        try {
            $quote = $this->quoteRepository->get($cartId);
            
            if ($quote->getCustomerId() != $customerId) {
                throw new LocalizedException(__('You are not authorized to access this cart.'));
            }

            $quote->setWalletAmountUsed(0);
            $quote->setBaseWalletAmountUsed(0);
            
            $this->quoteRepository->save($quote);
            $quote->collectTotals();
            $this->quoteRepository->save($quote);

            return true;
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Cart with ID "%1" does not exist.', $cartId));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function placeOrderWithWallet($customerId, $cartId)
    {
        /** @var \Formula\RazorpayApi\Api\Data\OrderResponseInterface $response */
        $response = $this->orderResponseFactory->create();

        try {
            $quote = $this->quoteRepository->get($cartId);

            if ($quote->getCustomerId() != $customerId) {
                throw new LocalizedException(__('You are not authorized to access this cart.'));
            }

            // Check if wallet has already been applied to cart
            $walletAmountUsed = $quote->getWalletAmountUsed();
            if (!$walletAmountUsed || $walletAmountUsed <= 0) {
                throw new LocalizedException(__('Please apply wallet amount to cart first using /carts/mine/wallet/apply'));
            }

            // Recalculate totals to get the current grand total after wallet application
            $quote->collectTotals();
            $grandTotal = $quote->getGrandTotal();

            // Only allow wallet-only orders (grand total must be 0 after wallet application)
            if ($grandTotal > 0) {
                throw new LocalizedException(__('Wallet payment is only allowed when the full order amount is covered by wallet. Current remaining amount: ' . $grandTotal . '. Please use Razorpay for partial payments.'));
            }

            // Check for cash on delivery payment method (not allowed with wallet)
            $paymentMethod = $quote->getPayment()->getMethod();
            if ($paymentMethod === 'cashondelivery') {
                throw new LocalizedException(__('Wallet payment cannot be combined with Cash on Delivery.'));
            }

            $quote->getPayment()->setMethod('walletpayment');

            $this->quoteRepository->save($quote);

            // Create order
            $orderId = $this->cartManagement->placeOrder($cartId);

            // Load the created order
            $order = $this->orderRepository->get($orderId);

            // Create Shiprocket shipment automatically
            $shipmentData = $this->createShiprocketShipment($order);

            // Build successful response
            $response->setSuccess(true);
            $response->setOrderId($orderId);
            $response->setIncrementId($order->getIncrementId());
            $response->setStatus($order->getStatus());
            $response->setState($order->getState());
            $response->setTotalAmount($order->getGrandTotal());
            $response->setCurrency($order->getOrderCurrencyCode());
            $response->setCreatedAt($order->getCreatedAt());

            // Add shipment information to response
            if ($shipmentData && $shipmentData['success']) {
                $response->setMessage('Order created with wallet payment and shipment scheduled successfully!');

                // Set shipment tracking fields
                $response->setShiprocketOrderId($shipmentData['shiprocket_order_id'] ?? null);
                $response->setShiprocketShipmentId($shipmentData['shipment_id'] ?? null);
                $response->setShiprocketAwbNumber($shipmentData['awb_code'] ?? null);
                $response->setShiprocketCourierName($shipmentData['courier_name'] ?? null);
            } else {
                $response->setMessage('Order created with wallet payment successfully!');
            }

        } catch (NoSuchEntityException $e) {
            // Build error response
            $response->setSuccess(false);
            $response->setError(true);
            $response->setMessage(__('Cart with ID "%1" does not exist.', $cartId));
            $response->setErrorCode($e->getCode());
        } catch (\Exception $e) {
            // Build error response
            $response->setSuccess(false);
            $response->setError(true);
            $response->setMessage($e->getMessage());
            $response->setErrorCode($e->getCode());
        }

        return $response;
    }

    /**
     * Create Shiprocket shipment for order
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    private function createShiprocketShipment($order)
    {
        try {
            $this->logger->info('Attempting to create Shiprocket shipment for wallet order: ' . $order->getIncrementId());

            // Create shipment through ShiprocketShipmentService
            $shipmentResult = $this->shiprocketShipmentService->createShipment($order);

            if ($shipmentResult['success']) {
                // Store shipment data in order
                $order->setData('shiprocket_order_id', $shipmentResult['shiprocket_order_id']);
                $order->setData('shiprocket_shipment_id', $shipmentResult['shipment_id']);
                $order->setData('shiprocket_awb_number', $shipmentResult['awb_code']);
                $order->setData('shiprocket_courier_name', $shipmentResult['courier_name']);

                // Update order status to shipment created
                $order->setStatus('shipment_created');

                // Add order comment
                $comment = sprintf(
                    'Shiprocket shipment created successfully. Shipment ID: %s, AWB: %s, Courier: %s',
                    $shipmentResult['shipment_id'],
                    $shipmentResult['awb_code'] ?: 'TBD',
                    $shipmentResult['courier_name'] ?: 'TBD'
                );
                $order->addStatusHistoryComment($comment, 'shipment_created');

                // Save order with shipment data
                $this->orderRepository->save($order);

                $this->logger->info('Shiprocket shipment created successfully for wallet order: ' . $order->getIncrementId(), $shipmentResult);

                return $shipmentResult;
            } else {
                // Log error but don't fail the order creation
                $this->logger->warning('Shiprocket shipment creation failed for wallet order: ' . $order->getIncrementId(), $shipmentResult);
                return ['success' => false, 'message' => 'Shipment creation failed'];
            }

        } catch (\Exception $e) {
            // Log error but don't fail the order creation
            $this->logger->error('Exception during shipment creation for wallet order: ' . $order->getIncrementId() . ' - ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateWalletBalance($customerId, $amount, $action = 'add')
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $currentBalance = $this->getWalletBalance($customerId);
            
            switch ($action) {
                case 'add':
                    $newBalance = $currentBalance + $amount;
                    break;
                case 'subtract':
                    $newBalance = max(0, $currentBalance - $amount);
                    break;
                case 'set':
                    $newBalance = max(0, $amount);
                    break;
                default:
                    throw new LocalizedException(__('Invalid action. Use add, subtract, or set.'));
            }

            $customer->setCustomAttribute('wallet_balance', $newBalance);
            $this->customerRepository->save($customer);

            return true;
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Customer with ID "%1" does not exist.', $customerId));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionHistory($customerId, $pageSize = 20, $currentPage = 1)
    {
        try {
            // Verify customer exists
            $this->customerRepository->getById($customerId);

            // Build sort order - newest first
            $sortOrder = $this->sortOrderBuilder
                ->setField('created_at')
                ->setDirection('DESC')
                ->create();

            // Build search criteria
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('customer_id', $customerId)
                ->addSortOrder($sortOrder)
                ->setPageSize($pageSize)
                ->setCurrentPage($currentPage)
                ->create();

            return $this->transactionRepository->getList($searchCriteria);
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Customer with ID "%1" does not exist.', $customerId));
        }
    }
}