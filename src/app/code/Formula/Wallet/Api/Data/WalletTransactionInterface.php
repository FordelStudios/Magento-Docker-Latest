<?php
namespace Formula\Wallet\Api\Data;

interface WalletTransactionInterface
{
    const TRANSACTION_ID = 'transaction_id';
    const CUSTOMER_ID = 'customer_id';
    const AMOUNT = 'amount';
    const TYPE = 'type';
    const BALANCE_BEFORE = 'balance_before';
    const BALANCE_AFTER = 'balance_after';
    const DESCRIPTION = 'description';
    const REFERENCE_TYPE = 'reference_type';
    const REFERENCE_ID = 'reference_id';
    const CREATED_AT = 'created_at';

    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';

    const REFERENCE_TYPE_ORDER = 'order';
    const REFERENCE_TYPE_REFUND = 'refund';
    const REFERENCE_TYPE_ORDER_CANCEL = 'order_cancel';
    const REFERENCE_TYPE_ORDER_RETURN = 'order_return';
    const REFERENCE_TYPE_ADMIN_API = 'admin_api';
    const REFERENCE_TYPE_ADMIN_PANEL = 'admin_panel';

    /**
     * Get transaction ID
     *
     * @return int|null
     */
    public function getTransactionId();

    /**
     * Set transaction ID
     *
     * @param int $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId);

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
     * Get amount
     *
     * @return float
     */
    public function getAmount();

    /**
     * Set amount
     *
     * @param float $amount
     * @return $this
     */
    public function setAmount($amount);

    /**
     * Get type
     *
     * @return string
     */
    public function getType();

    /**
     * Set type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type);

    /**
     * Get balance before transaction
     *
     * @return float
     */
    public function getBalanceBefore();

    /**
     * Set balance before transaction
     *
     * @param float $balanceBefore
     * @return $this
     */
    public function setBalanceBefore($balanceBefore);

    /**
     * Get balance after transaction
     *
     * @return float
     */
    public function getBalanceAfter();

    /**
     * Set balance after transaction
     *
     * @param float $balanceAfter
     * @return $this
     */
    public function setBalanceAfter($balanceAfter);

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Set description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * Get reference type
     *
     * @return string|null
     */
    public function getReferenceType();

    /**
     * Set reference type
     *
     * @param string $referenceType
     * @return $this
     */
    public function setReferenceType($referenceType);

    /**
     * Get reference ID
     *
     * @return int|null
     */
    public function getReferenceId();

    /**
     * Set reference ID
     *
     * @param int $referenceId
     * @return $this
     */
    public function setReferenceId($referenceId);

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);
}
