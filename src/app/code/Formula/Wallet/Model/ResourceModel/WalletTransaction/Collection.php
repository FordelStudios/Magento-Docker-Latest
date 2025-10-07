<?php
namespace Formula\Wallet\Model\ResourceModel\WalletTransaction;

use Formula\Wallet\Model\WalletTransaction;
use Formula\Wallet\Model\ResourceModel\WalletTransaction as WalletTransactionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'transaction_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(WalletTransaction::class, WalletTransactionResource::class);
    }
}
