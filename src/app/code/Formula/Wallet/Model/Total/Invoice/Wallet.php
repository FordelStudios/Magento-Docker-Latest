<?php
namespace Formula\Wallet\Model\Total\Invoice;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class Wallet extends AbstractTotal
{
    /**
     * Collect wallet total for invoice
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $walletAmountUsed = $order->getWalletAmountUsed();
        $baseWalletAmountUsed = $order->getBaseWalletAmountUsed();

        if ($walletAmountUsed && $walletAmountUsed > 0) {
            // Store wallet amount for tracking but DO NOT modify invoice grand total
            // Wallet is a payment method, not a discount
            $invoice->setWalletAmountUsed($walletAmountUsed);
            $invoice->setBaseWalletAmountUsed($baseWalletAmountUsed);
            
            // Grand total remains unchanged - wallet is handled as separate payment
        }

        return $this;
    }
}