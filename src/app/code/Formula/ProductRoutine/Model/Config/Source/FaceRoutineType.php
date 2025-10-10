<?php

namespace Formula\ProductRoutine\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class FaceRoutineType extends AbstractSource
{
    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (!$this->_options) {
            $this->_options = [
                ['label' => __('Cleanse'), 'value' => 'cleanse-face'],
                ['label' => __('Balance'), 'value' => 'balance-face'],
                ['label' => __('Treat'), 'value' => 'treat-face'],
                ['label' => __('Hydrate'), 'value' => 'hydrate-face'],
                ['label' => __('Protect'), 'value' => 'protect-face']
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
}
