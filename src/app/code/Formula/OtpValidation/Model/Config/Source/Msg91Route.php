<?php
namespace Formula\OtpValidation\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class Msg91Route implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '1', 'label' => __('Route 1 - Promotional SMS')],
            ['value' => '4', 'label' => __('Route 4 - Transactional SMS')]
        ];
    }
}