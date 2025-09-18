<?php
namespace Formula\Wallet\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class QuoteSubmitBefore implements ObserverInterface
{
    /**
     * Transfer wallet amount from quote to order
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        $walletAmountUsed = $quote->getWalletAmountUsed();
        $baseWalletAmountUsed = $quote->getBaseWalletAmountUsed();

        if ($walletAmountUsed && $walletAmountUsed > 0) {
            // Store wallet amount used
            $order->setWalletAmountUsed($walletAmountUsed);
            $order->setBaseWalletAmountUsed($baseWalletAmountUsed ?: $walletAmountUsed);

            // Restore original grand total to order (before wallet deduction)
            $originalGrandTotal = $order->getGrandTotal() + $walletAmountUsed;
            $originalBaseGrandTotal = $order->getBaseGrandTotal() + $baseWalletAmountUsed ?: $walletAmountUsed;

            $order->setGrandTotal($originalGrandTotal);
            $order->setBaseGrandTotal($originalBaseGrandTotal);
        }
    }
}