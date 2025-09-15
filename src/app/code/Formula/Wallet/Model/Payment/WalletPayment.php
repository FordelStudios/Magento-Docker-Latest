<?php
namespace Formula\Wallet\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Formula\Wallet\Api\WalletManagementInterface;

class WalletPayment extends AbstractMethod
{
    const PAYMENT_METHOD_WALLETPAYMENT_CODE = 'walletpayment';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_WALLETPAYMENT_CODE;

    /**
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * @var bool
     */
    protected $_canUseInternal = true;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var WalletManagementInterface
     */
    protected $walletManagement;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param CustomerSession $customerSession
     * @param WalletManagementInterface $walletManagement
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        CustomerSession $customerSession,
        WalletManagementInterface $walletManagement,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        
        $this->customerSession = $customerSession;
        $this->walletManagement = $walletManagement;
    }

    /**
     * Check if payment method is available for quote
     *
     * @param CartInterface $quote
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (!parent::isAvailable($quote)) {
            return false;
        }

        if (!$quote) {
            return false;
        }

        $customerId = $quote->getCustomerId();
        if (!$customerId) {
            return false;
        }

        $walletBalance = $this->walletManagement->getWalletBalance($customerId);
        $grandTotal = $quote->getGrandTotal();
        $walletAmountUsed = $quote->getWalletAmountUsed() ?: 0;

        // Only show wallet payment if wallet balance can cover the remaining amount
        return ($grandTotal - $walletAmountUsed) <= $walletBalance;
    }

    /**
     * Capture payment abstract method
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function capture(InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action is not available.'));
        }

        return $this;
    }
}