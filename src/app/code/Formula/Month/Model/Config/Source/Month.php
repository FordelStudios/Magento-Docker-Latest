<?php
declare(strict_types=1);

namespace Formula\Month\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class Month extends AbstractSource
{
    const JANUARY = 1;
    const FEBRUARY = 2;
    const MARCH = 3;
    const APRIL = 4;
    const MAY = 5;
    const JUNE = 6;
    const JULY = 7;
    const AUGUST = 8;
    const SEPTEMBER = 9;
    const OCTOBER = 10;
    const NOVEMBER = 11;
    const DECEMBER = 12;

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [
                ['label' => __('-- Please Select --'), 'value' => ''],
                ['label' => __('January'), 'value' => self::JANUARY],
                ['label' => __('February'), 'value' => self::FEBRUARY],
                ['label' => __('March'), 'value' => self::MARCH],
                ['label' => __('April'), 'value' => self::APRIL],
                ['label' => __('May'), 'value' => self::MAY],
                ['label' => __('June'), 'value' => self::JUNE],
                ['label' => __('July'), 'value' => self::JULY],
                ['label' => __('August'), 'value' => self::AUGUST],
                ['label' => __('September'), 'value' => self::SEPTEMBER],
                ['label' => __('October'), 'value' => self::OCTOBER],
                ['label' => __('November'), 'value' => self::NOVEMBER],
                ['label' => __('December'), 'value' => self::DECEMBER],
            ];
        }
        return $this->_options;
    }

    /**
     * Get option text by value
     *
     * @param string|int $value
     * @return string|false
     */
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
