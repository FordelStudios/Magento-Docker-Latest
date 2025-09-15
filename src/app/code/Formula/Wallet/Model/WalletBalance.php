<?php
namespace Formula\Wallet\Model;

use Formula\Wallet\Api\Data\WalletBalanceInterface;
use Magento\Framework\Model\AbstractModel;

class WalletBalance extends AbstractModel implements WalletBalanceInterface
{
    /**
     * Get customer ID
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get wallet balance
     *
     * @return float
     */
    public function getBalance()
    {
        return (float)$this->getData(self::BALANCE);
    }

    /**
     * Set wallet balance
     *
     * @param float $balance
     * @return $this
     */
    public function setBalance($balance)
    {
        return $this->setData(self::BALANCE, $balance);
    }

    /**
     * Get currency code
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->getData(self::CURRENCY_CODE);
    }

    /**
     * Set currency code
     *
     * @param string $currencyCode
     * @return $this
     */
    public function setCurrencyCode($currencyCode)
    {
        return $this->setData(self::CURRENCY_CODE, $currencyCode);
    }
}