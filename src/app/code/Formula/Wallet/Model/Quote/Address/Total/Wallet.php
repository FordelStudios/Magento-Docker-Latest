<?php
namespace Formula\Wallet\Model\Quote\Address\Total;

use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->setCode('wallet');
    }

    /**
     * Collect wallet totals for quote address
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
        if (!$walletAmount || $walletAmount <= 0) {
            return $this;
        }

        $baseWalletAmount = $quote->getBaseWalletAmountUsed() ?: $walletAmount;

        // Apply wallet amount as a deduction to the grand total
        $total->addTotalAmount('wallet', -$walletAmount);
        $total->addBaseTotalAmount('wallet', -$baseWalletAmount);

        // Store wallet amount for reference
        $total->setWalletAmountUsed($walletAmount);
        $total->setBaseWalletAmountUsed($baseWalletAmount);

        // Set wallet amount in quote for reference
        $quote->setWalletAmountUsed($walletAmount);
        $quote->setBaseWalletAmountUsed($baseWalletAmount);

        return $this;
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