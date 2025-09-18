<?php
namespace Formula\Wallet\Plugin\Sales;

use Magento\Sales\Model\Order\Payment\State\RegisterCaptureNotificationCommand;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Phrase;

class RegisterCaptureNotificationCommandPlugin
{
    /**
     * Fix captured amount message when wallet is used
     *
     * @param RegisterCaptureNotificationCommand $subject
     * @param \Closure $proceed
     * @param OrderPaymentInterface $payment
     * @param float $amount
     * @param OrderInterface $order
     * @return Phrase
     */
    public function aroundExecute(
        RegisterCaptureNotificationCommand $subject,
        \Closure $proceed,
        OrderPaymentInterface $payment,
        $amount,
        OrderInterface $order
    ) {
        $walletAmountUsed = $order->getWalletAmountUsed();

        if ($walletAmountUsed && $walletAmountUsed > 0) {
            // Calculate the actual payment amount (excluding wallet)
            $actualPaymentAmount = $order->getBaseGrandTotal() - $walletAmountUsed;

            // Call the original method with the corrected amount
            return $proceed($payment, $actualPaymentAmount, $order);
        }

        // No wallet used, proceed normally
        return $proceed($payment, $amount, $order);
    }
}