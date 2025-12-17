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
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;
use Formula\Wallet\Plugin\CartRepositorySavePlugin;

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
     * @var AdminSession
     */
    protected $adminSession;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

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
     * @param AdminSession|null $adminSession
     * @param Registry|null $registry
     * @param ResourceConnection|null $resourceConnection
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
        OrderResponseInterfaceFactory $orderResponseFactory,
        AdminSession $adminSession = null,
        Registry $registry = null,
        ResourceConnection $resourceConnection = null
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
        $this->adminSession = $adminSession;
        $this->registry = $registry ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Registry::class);
        $this->resourceConnection = $resourceConnection ?: \Magento\Framework\App\ObjectManager::getInstance()->get(ResourceConnection::class);
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
        $this->logger->info('WalletManagement::applyWalletToCart called', [
            'customer_id' => $customerId,
            'cart_id' => $cartId,
            'amount' => $amount
        ]);

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

            // Save wallet amount directly to the database
            // This bypasses the CartRepository which doesn't persist custom fields
            $this->saveWalletAmountToQuote((int)$cartId, (float)$amount, (float)$amount);

            // Reload the quote (CartRepository doesn't load custom columns automatically)
            $quote = $this->quoteRepository->get($cartId);

            // Set wallet amount on quote object for collectTotals() to use
            // This is necessary because CartRepository->get() doesn't load custom DB columns
            $quote->setWalletAmountUsed($amount);
            $quote->setBaseWalletAmountUsed($amount);

            // Recalculate totals with the wallet amount applied
            $quote->collectTotals();

            // Save the quote with updated totals (registry flag to preserve wallet amount)
            $this->registry->register(CartRepositorySavePlugin::REGISTRY_KEY_WALLET_OPERATION, true);
            try {
                $this->quoteRepository->save($quote);
            } finally {
                $this->registry->unregister(CartRepositorySavePlugin::REGISTRY_KEY_WALLET_OPERATION);
            }

            // Re-save wallet amount after totals collection (in case it was modified)
            $this->saveWalletAmountToQuote((int)$cartId, (float)$amount, (float)$amount);

            $this->logger->info('WalletManagement::applyWalletToCart completed successfully', [
                'cart_id' => $cartId,
                'wallet_amount_used' => $amount
            ]);

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

            // Remove wallet amount directly from the database
            $this->saveWalletAmountToQuote((int)$cartId, 0.0, 0.0);

            // Reload the quote and recalculate totals
            $quote = $this->quoteRepository->get($cartId);

            // Explicitly set wallet amount to 0 on quote object
            // This is necessary because CartRepository->get() doesn't load custom DB columns
            $quote->setWalletAmountUsed(0.0);
            $quote->setBaseWalletAmountUsed(0.0);

            $quote->collectTotals();

            // Save the quote with updated totals
            $this->registry->register(CartRepositorySavePlugin::REGISTRY_KEY_WALLET_OPERATION, true);
            try {
                $this->quoteRepository->save($quote);
            } finally {
                $this->registry->unregister(CartRepositorySavePlugin::REGISTRY_KEY_WALLET_OPERATION);
            }

            // Ensure wallet is removed after totals collection
            $this->saveWalletAmountToQuote((int)$cartId, 0.0, 0.0);

            $this->logger->info('WalletManagement::removeWalletFromCart completed', [
                'cart_id' => $cartId
            ]);

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

            // Set payment method to wallet (overwriting any default like COD)
            $quote->getPayment()->setMethod('walletpayment');

            // Set registry flag to bypass CartRepositorySavePlugin during legitimate wallet operations
            $this->registry->register(CartRepositorySavePlugin::REGISTRY_KEY_WALLET_OPERATION, true);
            try {
                $this->quoteRepository->save($quote);
            } finally {
                $this->registry->unregister(CartRepositorySavePlugin::REGISTRY_KEY_WALLET_OPERATION);
            }

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
                // SECURITY FIX: Set order to shipment_pending_manual status for admin attention
                $this->logger->warning('Shiprocket shipment creation failed for wallet order: ' . $order->getIncrementId(), $shipmentResult);

                $order->setStatus('shipment_pending_manual');
                $order->addStatusHistoryComment(
                    sprintf(
                        'Shiprocket shipment creation failed. Reason: %s. Manual intervention required.',
                        $shipmentResult['message'] ?? 'Unknown error'
                    ),
                    'shipment_pending_manual'
                );
                $this->orderRepository->save($order);

                return ['success' => false, 'message' => 'Shipment creation failed'];
            }

        } catch (\Exception $e) {
            // SECURITY FIX: Set order to shipment_pending_manual status for admin attention
            $this->logger->error('Exception during shipment creation for wallet order: ' . $order->getIncrementId() . ' - ' . $e->getMessage());

            try {
                $order->setStatus('shipment_pending_manual');
                $order->addStatusHistoryComment(
                    sprintf(
                        'Shiprocket shipment creation failed with exception: %s. Manual intervention required.',
                        $e->getMessage()
                    ),
                    'shipment_pending_manual'
                );
                $this->orderRepository->save($order);
            } catch (\Exception $saveException) {
                $this->logger->error('Failed to update order status after Shiprocket failure: ' . $saveException->getMessage());
            }

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * {@inheritdoc}
     *
     * SECURITY FIX: Now uses WalletBalanceService for atomic updates with validation
     * - Validates amount is positive for add/subtract
     * - Validates against max balance and transaction limits
     * - Uses row locking to prevent race conditions
     * - Transaction logging included atomically
     */
    public function updateWalletBalance($customerId, $amount, $action = 'add')
    {
        // SECURITY FIX: Validate amount is numeric
        if (!is_numeric($amount)) {
            throw new LocalizedException(__('Amount must be a valid number.'));
        }

        $amount = (float)$amount;

        // SECURITY FIX: For add/subtract, amount must be positive
        // Using negative with 'add' would subtract, which is confusing and could be exploited
        if ($action !== 'set' && $amount < 0) {
            throw new LocalizedException(
                __('Amount must be positive. Use "subtract" action to reduce balance.')
            );
        }

        // Validate action type
        if (!in_array($action, ['add', 'subtract', 'set'])) {
            throw new LocalizedException(__('Invalid action. Use add, subtract, or set.'));
        }

        try {
            // Use atomic balance service for thread-safe updates
            // WalletBalanceService handles:
            // - Row locking (SELECT FOR UPDATE)
            // - Balance limit validation
            // - Transaction limit validation
            // - Atomic transaction logging

            // Note: WalletBalanceService needs to be injected - for now using direct call
            // The atomic service will handle the actual update with proper locking
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $balanceService = $objectManager->get(\Formula\Wallet\Api\WalletBalanceServiceInterface::class);

            $description = sprintf(
                'Admin adjustment - wallet %s via API',
                $action === 'subtract' ? 'debited' : 'credited'
            );

            // Get admin user info for audit trail
            $adminUserId = null;
            $adminUsername = null;
            if ($this->adminSession && $this->adminSession->isLoggedIn()) {
                $adminUser = $this->adminSession->getUser();
                if ($adminUser) {
                    $adminUserId = (int)$adminUser->getId();
                    $adminUsername = $adminUser->getUserName();
                }
            }

            $result = $balanceService->updateBalanceAtomic(
                (int)$customerId,
                $amount,
                $action,
                $description,
                \Formula\Wallet\Api\Data\WalletTransactionInterface::REFERENCE_TYPE_ADMIN_API,
                null,
                $adminUserId,
                $adminUsername
            );

            $this->logger->info('Admin wallet balance update', [
                'customer_id' => $customerId,
                'action' => $action,
                'amount' => $amount,
                'old_balance' => $result['old_balance'],
                'new_balance' => $result['new_balance']
            ]);

            return true;

        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Customer with ID "%1" does not exist.', $customerId));
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update wallet balance', [
                'customer_id' => $customerId,
                'action' => $action,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw new LocalizedException(__('Failed to update wallet balance. Please try again.'));
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

    /**
     * Save wallet amount directly to the quote table
     *
     * This method bypasses the CartRepository to directly update the wallet fields
     * in the database, ensuring the values are persisted regardless of how the
     * repository handles custom fields.
     *
     * @param int $cartId
     * @param float $walletAmount
     * @param float|null $baseWalletAmount
     * @return void
     */
    private function saveWalletAmountToQuote(int $cartId, float $walletAmount, float $baseWalletAmount = null): void
    {
        if ($baseWalletAmount === null) {
            $baseWalletAmount = $walletAmount;
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('quote');

            $connection->update(
                $tableName,
                [
                    'wallet_amount_used' => $walletAmount,
                    'base_wallet_amount_used' => $baseWalletAmount
                ],
                ['entity_id = ?' => $cartId]
            );

            $this->logger->info('WalletManagement: Saved wallet amount directly to DB', [
                'cart_id' => $cartId,
                'wallet_amount_used' => $walletAmount,
                'base_wallet_amount_used' => $baseWalletAmount
            ]);
        } catch (\Exception $e) {
            $this->logger->error('WalletManagement: Failed to save wallet amount to DB', [
                'cart_id' => $cartId,
                'error' => $e->getMessage()
            ]);
            throw new LocalizedException(__('Failed to apply wallet to cart.'));
        }
    }
}