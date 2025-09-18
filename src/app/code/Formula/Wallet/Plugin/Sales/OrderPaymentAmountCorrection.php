<?php
namespace Formula\Wallet\Plugin\Sales;

use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Exception\LocalizedException;

class OrderPaymentAmountCorrection
{
    /**
     * Get actual payment amount excluding wallet
     *
     * @param Payment $payment
     * @return float|null
     */
    private function getActualPaymentAmount(Payment $payment)
    {
        $order = $payment->getOrder();
        if (!$order) {
            return null;
        }

        $walletAmountUsed = $order->getWalletAmountUsed();
        if (!$walletAmountUsed || $walletAmountUsed <= 0) {
            return null;
        }

        return $order->getBaseGrandTotal() - $walletAmountUsed;
    }

    /**
     * Correct payment amounts when wallet is used
     *
     * @param Payment $subject
     * @param array $result
     * @return array
     */
    public function afterToArray(Payment $subject, array $result)
    {
        $actualPaymentAmount = $this->getActualPaymentAmount($subject);

        if ($actualPaymentAmount !== null) {
            // Update all payment amount fields to reflect only the non-wallet portion
            $fieldsToUpdate = [
                'amount_authorized',
                'base_amount_authorized',
                'amount_paid',
                'base_amount_paid',
                'base_amount_paid_online',
                'amount_ordered',
                'base_amount_ordered'
            ];

            foreach ($fieldsToUpdate as $field) {
                if (isset($result[$field])) {
                    $result[$field] = $actualPaymentAmount;
                }
            }
        }

        return $result;
    }

    /**
     * Correct base amount authorized
     */
    public function beforeSetBaseAmountAuthorized(Payment $subject, $amount)
    {
        $actualPaymentAmount = $this->getActualPaymentAmount($subject);
        return $actualPaymentAmount !== null ? [$actualPaymentAmount] : [$amount];
    }

    /**
     * Correct amount authorized
     */
    public function beforeSetAmountAuthorized(Payment $subject, $amount)
    {
        $actualPaymentAmount = $this->getActualPaymentAmount($subject);
        return $actualPaymentAmount !== null ? [$actualPaymentAmount] : [$amount];
    }

    /**
     * Correct base amount paid
     */
    public function beforeSetBaseAmountPaid(Payment $subject, $amount)
    {
        $actualPaymentAmount = $this->getActualPaymentAmount($subject);
        return $actualPaymentAmount !== null ? [$actualPaymentAmount] : [$amount];
    }

    /**
     * Correct amount paid
     */
    public function beforeSetAmountPaid(Payment $subject, $amount)
    {
        $actualPaymentAmount = $this->getActualPaymentAmount($subject);
        return $actualPaymentAmount !== null ? [$actualPaymentAmount] : [$amount];
    }

    /**
     * Correct base amount paid online
     */
    public function beforeSetBaseAmountPaidOnline(Payment $subject, $amount)
    {
        $actualPaymentAmount = $this->getActualPaymentAmount($subject);
        return $actualPaymentAmount !== null ? [$actualPaymentAmount] : [$amount];
    }

    /**
     * Correct base amount ordered
     */
    public function beforeSetBaseAmountOrdered(Payment $subject, $amount)
    {
        $actualPaymentAmount = $this->getActualPaymentAmount($subject);
        return $actualPaymentAmount !== null ? [$actualPaymentAmount] : [$amount];
    }

    /**
     * Correct amount ordered
     */
    public function beforeSetAmountOrdered(Payment $subject, $amount)
    {
        $actualPaymentAmount = $this->getActualPaymentAmount($subject);
        return $actualPaymentAmount !== null ? [$actualPaymentAmount] : [$amount];
    }
}