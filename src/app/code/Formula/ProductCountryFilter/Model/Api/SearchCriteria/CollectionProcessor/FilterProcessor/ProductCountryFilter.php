<?php
/**
 * Copyright © Formula. All rights reserved.
 */
namespace Formula\ProductCountryFilter\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Custom filter processor for country_of_manufacture attribute
 * Handles special "global" value to filter products from non-specific countries
 */
class ProductCountryFilter implements CustomFilterInterface
{
    /**
     * Country codes to exclude when filtering for "global" products
     * IN = India, JP = Japan, KR = South Korea, ZA = South Africa
     */
    private const EXCLUDED_COUNTRIES = ['IN', 'JP', 'KR', 'ZA'];

    /**
     * Special filter value to get products from rest of the world
     */
    private const GLOBAL_FILTER_VALUE = 'global';

    /**
     * Apply country_of_manufacture Filter to Product Collection
     *
     * @param Filter $filter
     * @param AbstractDb $collection
     * @return bool Whether the filter is applied
     */
    public function apply(Filter $filter, AbstractDb $collection)
    {
        /** @var Collection $collection */
        $value = $filter->getValue();
        $conditionType = $filter->getConditionType() ?: 'eq';

        // Check if this is a combined filter (contains comma)
        if (is_string($value) && strpos($value, ',') !== false) {
            $this->applyCombinedFilter($collection, $value);
            return true;
        }

        // Check if this is the special "global" filter
        if (strtolower($value) === self::GLOBAL_FILTER_VALUE) {
            $this->applyGlobalFilter($collection);
            return true;
        }

        // Apply standard filter for specific country codes
        $this->applyStandardFilter($collection, $value, $conditionType);
        return true;
    }

    /**
     * Apply combined filter for multiple countries (e.g., "global,KR,JP")
     *
     * @param Collection $collection
     * @param string $combinedValue
     * @return void
     */
    private function applyCombinedFilter(Collection $collection, string $combinedValue): void
    {
        $values = explode(',', $combinedValue);
        $hasGlobal = false;
        $specificCountries = [];
        $excludedCountries = self::EXCLUDED_COUNTRIES;

        // Parse values
        foreach ($values as $value) {
            $value = trim($value);
            if (strtolower($value) === self::GLOBAL_FILTER_VALUE) {
                $hasGlobal = true;
            } else {
                $specificCountries[] = $value;
                // Remove this country from excluded list
                $excludedCountries = array_diff($excludedCountries, [$value]);
            }
        }

        // Build OR conditions
        $conditions = [];

        if ($hasGlobal) {
            // Add NULL condition
            $conditions[] = ['null' => true];

            // Add NOT IN condition for remaining excluded countries
            if (!empty($excludedCountries)) {
                $conditions[] = ['nin' => array_values($excludedCountries)];
            }
        }

        // Add specific countries condition
        if (!empty($specificCountries)) {
            if (count($specificCountries) === 1) {
                $conditions[] = ['eq' => $specificCountries[0]];
            } else {
                $conditions[] = ['in' => $specificCountries];
            }
        }

        // Apply combined OR filter
        if (!empty($conditions)) {
            $collection->addFieldToFilter('country_of_manufacture', $conditions);
        }
    }

    /**
     * Apply global filter: products with NULL country OR countries not in excluded list
     *
     * @param Collection $collection
     * @return void
     */
    private function applyGlobalFilter(Collection $collection): void
    {
        $collection->addFieldToFilter(
            'country_of_manufacture',
            [
                ['null' => true],
                ['nin' => self::EXCLUDED_COUNTRIES]
            ]
        );
    }

    /**
     * Apply standard country filter based on condition type
     *
     * @param Collection $collection
     * @param string $value
     * @param string $conditionType
     * @return void
     */
    private function applyStandardFilter(Collection $collection, string $value, string $conditionType): void
    {
        // Handle 'in' and 'nin' condition types that may have comma-separated values
        if (in_array($conditionType, ['in', 'nin']) && is_string($value)) {
            $value = explode(',', $value);
        }

        $collection->addFieldToFilter(
            'country_of_manufacture',
            [$conditionType => $value]
        );
    }
}
