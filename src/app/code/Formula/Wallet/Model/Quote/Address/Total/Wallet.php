<?php
namespace Formula\Wallet\Model\Quote\Address\Total;

use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Wallet extends AbstractTotal
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->setCode('wallet');
    }

    /**
     * Collect wallet totals for quote address
     *
     * SECURITY FIX: Auto-adjusts wallet amount if it exceeds:
     * 1. The current grand total (after cart changes)
     * 2. The customer's actual wallet balance
     * This prevents customers from being confused or exploiting stale wallet amounts.
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        if (!$shippingAssignment->getItems()) {
            return $this;
        }

        $walletAmount = $quote->getWalletAmountUsed();
        $this->logger->info('Wallet Total Collector: collect() called', [
            'quote_id' => $quote->getId(),
            'wallet_amount_from_quote' => $walletAmount,
            'data_wallet' => $quote->getData('wallet_amount_used'),
            'grand_total_before' => $total->getGrandTotal()
        ]);

        if (!$walletAmount || $walletAmount <= 0) {
            $this->logger->info('Wallet Total Collector: No wallet amount, skipping');
            return $this;
        }

        // Get current grand total BEFORE wallet deduction
        // Note: grand_total may be null if this collector runs before the grand_total collector
        $currentGrandTotal = $total->getGrandTotal();
        $currentBaseGrandTotal = $total->getBaseGrandTotal();

        // If grand_total is not yet calculated, use subtotal + shipping as fallback
        if ($currentGrandTotal === null || $currentGrandTotal <= 0) {
            $currentGrandTotal = $total->getSubtotal() + $total->getShippingAmount();
            $currentBaseGrandTotal = $total->getBaseSubtotal() + $total->getBaseShippingAmount();
        }

        // SECURITY FIX: Cap wallet amount to current grand total
        // This prevents issues when cart items are removed after wallet was applied
        if ($currentGrandTotal > 0 && $walletAmount > $currentGrandTotal) {
            $walletAmount = $currentGrandTotal;
            $quote->setWalletAmountUsed($walletAmount);
        }

        // SECURITY FIX: Validate against customer's actual wallet balance
        $customerId = $quote->getCustomerId();
        if ($customerId) {
            $customerBalance = $this->getCustomerWalletBalance($customerId);
            if ($walletAmount > $customerBalance) {
                $walletAmount = $customerBalance;
                $quote->setWalletAmountUsed($walletAmount);
            }
        }

        // If wallet amount became 0 after adjustments, skip deduction
        if ($walletAmount <= 0) {
            $quote->setWalletAmountUsed(0);
            $quote->setBaseWalletAmountUsed(0);
            return $this;
        }

        $baseWalletAmount = $quote->getBaseWalletAmountUsed() ?: $walletAmount;

        // Apply same caps to base wallet amount
        if ($currentBaseGrandTotal > 0 && $baseWalletAmount > $currentBaseGrandTotal) {
            $baseWalletAmount = $currentBaseGrandTotal;
            $quote->setBaseWalletAmountUsed($baseWalletAmount);
        }

        // Apply wallet amount as a deduction to the grand total
        $total->addTotalAmount('wallet', -$walletAmount);
        $total->addBaseTotalAmount('wallet', -$baseWalletAmount);

        // Store wallet amount for reference
        $total->setWalletAmountUsed($walletAmount);
        $total->setBaseWalletAmountUsed($baseWalletAmount);

        // Set wallet amount in quote for reference
        $quote->setWalletAmountUsed($walletAmount);
        $quote->setBaseWalletAmountUsed($baseWalletAmount);

        $this->logger->info('Wallet Total Collector: Applied wallet deduction', [
            'wallet_amount' => $walletAmount,
            'grand_total_after' => $total->getGrandTotal()
        ]);

        return $this;
    }

    /**
     * Get customer's wallet balance
     *
     * @param int $customerId
     * @return float
     */
    private function getCustomerWalletBalance($customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $walletBalance = $customer->getCustomAttribute('wallet_balance');
            return $walletBalance ? (float)$walletBalance->getValue() : 0.0;
        } catch (NoSuchEntityException $e) {
            return 0.0;
        }
    }

    /**
     * Fetch wallet total
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $walletAmount = $quote->getWalletAmountUsed();
        
        if ($walletAmount && $walletAmount > 0) {
            return [
                'code' => $this->getCode(),
                'title' => __('Paid with Wallet'),
                'value' => -$walletAmount
            ];
        }
        
        return null;
    }

    /**
     * Get Subtotal label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Paid with Wallet');
    }
}