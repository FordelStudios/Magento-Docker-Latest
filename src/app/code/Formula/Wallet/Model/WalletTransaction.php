<?php
namespace Formula\Wallet\Model;

use Formula\Wallet\Api\Data\WalletTransactionInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;

class WalletTransaction extends AbstractModel implements WalletTransactionInterface, IdentityInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'formula_wallet_transaction';

    /**
     * @var string
     */
    protected $_cacheTag = 'formula_wallet_transaction';

    /**
     * @var string
     */
    protected $_eventPrefix = 'formula_wallet_transaction';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Formula\Wallet\Model\ResourceModel\WalletTransaction::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionId()
    {
        return $this->getData(self::TRANSACTION_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setTransactionId($transactionId)
    {
        return $this->setData(self::TRANSACTION_ID, $transactionId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAmount()
    {
        return $this->getData(self::AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setAmount($amount)
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function getBalanceBefore()
    {
        return $this->getData(self::BALANCE_BEFORE);
    }

    /**
     * {@inheritdoc}
     */
    public function setBalanceBefore($balanceBefore)
    {
        return $this->setData(self::BALANCE_BEFORE, $balanceBefore);
    }

    /**
     * {@inheritdoc}
     */
    public function getBalanceAfter()
    {
        return $this->getData(self::BALANCE_AFTER);
    }

    /**
     * {@inheritdoc}
     */
    public function setBalanceAfter($balanceAfter)
    {
        return $this->setData(self::BALANCE_AFTER, $balanceAfter);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceType()
    {
        return $this->getData(self::REFERENCE_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setReferenceType($referenceType)
    {
        return $this->setData(self::REFERENCE_TYPE, $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceId()
    {
        return $this->getData(self::REFERENCE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setReferenceId($referenceId)
    {
        return $this->setData(self::REFERENCE_ID, $referenceId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
