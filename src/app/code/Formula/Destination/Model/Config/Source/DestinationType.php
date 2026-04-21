<?php
declare(strict_types=1);

namespace Formula\Destination\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class DestinationType extends AbstractSource
{
    const CITIES = 1;
    const BEACHES = 2;
    const HILL_STATIONS = 3;
    const MOUNTAINS = 4;
    const DESERTS = 5;

    public function getAllOptions(): array
    {
        if (!$this->_options) {
            $this->_options = [
                ['label' => __('-- Please Select --'), 'value' => ''],
                ['label' => __('Cities'), 'value' => self::CITIES],
                ['label' => __('Beaches'), 'value' => self::BEACHES],
                ['label' => __('Hill stations'), 'value' => self::HILL_STATIONS],
                ['label' => __('Mountains'), 'value' => self::MOUNTAINS],
                ['label' => __('Deserts'), 'value' => self::DESERTS],
            ];
        }
        return $this->_options;
    }

    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
}
