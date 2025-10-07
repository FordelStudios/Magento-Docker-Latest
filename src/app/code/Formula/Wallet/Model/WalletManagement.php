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
     * @param CustomerRepositoryInterface $customerRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param CartManagementInterface $cartManagement
     * @param WalletBalanceInterfaceFactory $walletBalanceFactory
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param WalletTransactionRepositoryInterface $transactionRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
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
        SortOrderBuilder $sortOrderBuilder
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

            $orderId = $this->cartManagement->placeOrder($cartId);

            return $orderId;
        } catch (NoSuchEntityException $e) {
            throw new NoSuchEntityException(__('Cart with ID "%1" does not exist.', $cartId));
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