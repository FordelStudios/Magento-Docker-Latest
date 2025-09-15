<?php
namespace Formula\Wallet\Plugin\Sales;

use Magento\Sales\Api\Data\OrderInterface;

class OrderExtensionAttributes
{
    /**
     * Add wallet_amount_used to order extension attributes
     *
     * @param OrderInterface $order
     * @param mixed $result
     * @return mixed
     */
    public function afterGetExtensionAttributes(OrderInterface $order, $result)
    {
        if ($result === null) {
            $result = $order->getExtensionAttributes();
        }
        
        if ($result && method_exists($result, 'setWalletAmountUsed')) {
            $result->setWalletAmountUsed($order->getWalletAmountUsed());
        }
        
        return $result;
    }
}