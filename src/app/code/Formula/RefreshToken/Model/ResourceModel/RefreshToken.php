<?php
namespace Formula\RefreshToken\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RefreshToken extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('formula_refresh_token', 'entity_id');
    }
}
