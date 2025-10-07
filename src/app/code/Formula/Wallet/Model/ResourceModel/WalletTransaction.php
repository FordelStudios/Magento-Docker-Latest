<?php
namespace Formula\Wallet\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class WalletTransaction extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('formula_wallet_transaction', 'transaction_id');
    }
}
