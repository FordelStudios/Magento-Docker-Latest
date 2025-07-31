<?php

namespace Formula\ProductRoutine\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class RoutineTiming extends AbstractSource
{
    const DAY = 1;
    const NIGHT = 2;
    const ANYTIME = 3;

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [
                ['label' => __('-- Please Select --'), 'value' => 0 ],
                ['label' => __('Day'), 'value' => self::DAY],
                ['label' => __('Night'), 'value' => self::NIGHT],
                ['label' => __('Anytime'), 'value' => self::ANYTIME]
            ];
        }
        return $this->_options;
    }

    /**
     * Get option text
     *
     * @param string|integer $value
     * @return string|bool
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

    /**
     * Get option array for use in forms
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function getOptionsArray()
    {
        $options = [];
        foreach ($this->getAllOptions() as $option) {
            $options[$option['value']] = $option['label'];
        }
        return $options;
    }

    /**
     * Get option value by text
     *
     * @param string $text
     * @return int|bool
     */
    public function getValueByText($text)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['label'] == $text) {
                return $option['value'];
            }
        }
        return false;
    }

    /**
     * Get string value for API responses
     *
     * @param int $value
     * @return string
     */
    public function getStringValue($value)
    {
        switch ($value) {
            case self::DAY:
                return 'day';
            case self::NIGHT:
                return 'night';
            case self::ANYTIME:
                return 'anytime';
            default:
                return '';
        }
    }
}