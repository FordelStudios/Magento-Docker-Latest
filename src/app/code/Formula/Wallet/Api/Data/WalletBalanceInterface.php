<?php
namespace Formula\Wallet\Api\Data;

interface WalletBalanceInterface
{
    const CUSTOMER_ID = 'customer_id';
    const BALANCE = 'balance';
    const CURRENCY_CODE = 'currency_code';

    /**
     * Get customer ID
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Get wallet balance
     *
     * @return float
     */
    public function getBalance();

    /**
     * Set wallet balance
     *
     * @param float $balance
     * @return $this
     */
    public function setBalance($balance);

    /**
     * Get currency code
     *
     * @return string
     */
    public function getCurrencyCode();

    /**
     * Set currency code
     *
     * @param string $currencyCode
     * @return $this
     */
    public function setCurrencyCode($currencyCode);
}