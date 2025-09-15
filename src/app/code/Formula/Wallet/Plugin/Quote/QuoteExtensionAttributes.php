<?php
namespace Formula\Wallet\Plugin\Quote;

use Magento\Quote\Api\Data\CartInterface;

class QuoteExtensionAttributes
{
    /**
     * Add wallet_amount_used to quote extension attributes
     *
     * @param CartInterface $quote
     * @param mixed $result
     * @return mixed
     */
    public function afterGetExtensionAttributes(CartInterface $quote, $result)
    {
        if ($result === null) {
            $result = $quote->getExtensionAttributes();
        }
        
        if ($result && method_exists($result, 'setWalletAmountUsed')) {
            $result->setWalletAmountUsed($quote->getWalletAmountUsed());
        }
        
        return $result;
    }
}