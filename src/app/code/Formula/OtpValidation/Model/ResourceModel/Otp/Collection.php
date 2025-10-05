<?php
namespace Formula\OtpValidation\Model\ResourceModel\Otp;

use Formula\OtpValidation\Model\Otp;
use Formula\OtpValidation\Model\ResourceModel\Otp as OtpResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(Otp::class, OtpResourceModel::class);
    }
}