<?php
namespace Formula\OtpValidation\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Otp extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('formula_otp_validation', 'entity_id');
    }
}