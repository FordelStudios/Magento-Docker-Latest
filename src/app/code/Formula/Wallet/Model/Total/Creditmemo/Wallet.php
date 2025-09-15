<?php
namespace Formula\Wallet\Model\Total\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class Wallet extends AbstractTotal
{
    /**
     * Collect wallet total for credit memo
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $walletAmountUsed = $order->getWalletAmountUsed();
        $baseWalletAmountUsed = $order->getBaseWalletAmountUsed();

        if ($walletAmountUsed && $walletAmountUsed > 0) {
            // Store wallet amount for tracking but DO NOT modify credit memo grand total
            // Wallet refund should be handled separately as it goes back to wallet balance
            $creditmemo->setWalletAmountUsed($walletAmountUsed);
            $creditmemo->setBaseWalletAmountUsed($baseWalletAmountUsed);
            
            // Grand total remains unchanged - wallet refund is handled separately
        }

        return $this;
    }
}