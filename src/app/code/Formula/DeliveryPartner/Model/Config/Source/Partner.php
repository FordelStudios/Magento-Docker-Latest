<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Model\Config\Source;

use Formula\DeliveryPartner\Model\PartnerCode;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Admin config source model for the "Active Delivery Partner" select field.
 */
class Partner implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => PartnerCode::SHIPROCKET, 'label' => __('Shiprocket')],
            ['value' => PartnerCode::SHADOWFAX, 'label' => __('ShadowFax')],
        ];
    }
}
