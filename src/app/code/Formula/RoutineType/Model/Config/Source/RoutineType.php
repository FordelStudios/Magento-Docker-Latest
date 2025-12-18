<?php
declare(strict_types=1);

namespace Formula\RoutineType\Model\Config\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class RoutineType extends AbstractSource
{
    const FACE = 1;
    const HAIR = 2;
    const BODY = 3;

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
                ['label' => __('Face'), 'value' => self::FACE],
                ['label' => __('Hair'), 'value' => self::HAIR],
                ['label' => __('Body'), 'value' => self::BODY],
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
