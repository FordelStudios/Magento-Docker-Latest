<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Zoho Books data-center region options
 *
 * Determines the Zoho API/accounts host suffix used for OAuth + API calls
 * (e.g. https://www.zohoapis{region}, https://accounts.zoho{region}).
 */
class Region implements OptionSourceInterface
{
    public const INDIA = '.in';
    public const US = '.com';
    public const EU = '.eu';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::INDIA, 'label' => __('India (.in)')],
            ['value' => self::US, 'label' => __('United States (.com)')],
            ['value' => self::EU, 'label' => __('Europe (.eu)')],
        ];
    }
}
