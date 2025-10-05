<?php
namespace Formula\OtpValidation\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class SmsProvider implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'msg91', 'label' => __('MSG91')],
            ['value' => '2factor', 'label' => __('2Factor')]
        ];
    }
}