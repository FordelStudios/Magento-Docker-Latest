<?php

namespace Formula\Gender\Plugin;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

class ProductCollectionPlugin
{
    /**
     * Add gender attribute to product collection
     *
     * @param Collection $subject
     * @param Collection $result
     * @return Collection
     */
    public function afterLoad(Collection $subject, Collection $result)
    {
        if (!$subject->isLoaded()) {
            $subject->addAttributeToSelect('gender');
        }
        return $result;
    }

    /**
     * Add gender filtering support for multi-select values
     *
     * @param Collection $subject
     * @param \Closure $proceed
     * @param string $attribute
     * @param mixed $condition
     * @param string $joinType
     * @return Collection
     */
    public function aroundAddAttributeToFilter(Collection $subject, \Closure $proceed, $attribute, $condition = null, $joinType = 'inner')
    {
        if ($attribute === 'gender' && is_array($condition)) {
            // Handle multi-select gender filtering
            if (isset($condition['in'])) {
                $genderValues = $condition['in'];
                $orConditions = [];
                foreach ($genderValues as $value) {
                    $orConditions[] = ['finset' => $value];
                }
                if (count($orConditions) > 1) {
                    $subject->addAttributeToFilter('gender', $orConditions);
                } else {
                    $subject->addAttributeToFilter('gender', ['finset' => $genderValues[0]]);
                }
                return $subject;
            } elseif (isset($condition['finset'])) {
                // Already in correct format for multi-select
                return $proceed($attribute, $condition, $joinType);
            }
        }

        return $proceed($attribute, $condition, $joinType);
    }
}