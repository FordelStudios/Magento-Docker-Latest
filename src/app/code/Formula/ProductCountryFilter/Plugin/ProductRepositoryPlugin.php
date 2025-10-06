<?php
/**
 * Copyright © Formula. All rights reserved.
 */
namespace Formula\ProductCountryFilter\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Plugin to handle multiple country_of_manufacture filters with OR logic
 */
class ProductRepositoryPlugin
{
    /**
     * Country codes to exclude when filtering for "global" products
     */
    private const EXCLUDED_COUNTRIES = ['IN', 'JP', 'KR', 'ZA'];

    /**
     * Special filter value to get products from rest of the world
     */
    private const GLOBAL_FILTER_VALUE = 'global';

    /**
     * Field name for country of manufacture
     */
    private const COUNTRY_FIELD = 'country_of_manufacture';

    /**
     * Around plugin for getList to handle multiple country filters
     *
     * @param ProductRepositoryInterface $subject
     * @param callable $proceed
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function aroundGetList(
        ProductRepositoryInterface $subject,
        callable $proceed,
        SearchCriteriaInterface $searchCriteria
    ) {
        // Check if we have multiple country_of_manufacture filters in any filter group
        $modifiedSearchCriteria = $this->preprocessSearchCriteria($searchCriteria);

        // Continue with normal processing
        return $proceed($modifiedSearchCriteria);
    }

    /**
     * Preprocess SearchCriteria to handle multiple country filters
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchCriteriaInterface
     */
    private function preprocessSearchCriteria(SearchCriteriaInterface $searchCriteria): SearchCriteriaInterface
    {
        $filterGroups = $searchCriteria->getFilterGroups();
        $hasMultipleCountryFilters = false;

        // Check if any filter group has multiple country filters
        foreach ($filterGroups as $filterGroup) {
            $countryFilters = $this->getCountryFilters($filterGroup);
            if (count($countryFilters) > 1) {
                $hasMultipleCountryFilters = true;
                break;
            }
        }

        // If no multiple country filters, return original SearchCriteria
        if (!$hasMultipleCountryFilters) {
            return $searchCriteria;
        }

        // We need to create modified filter groups
        $modifiedGroups = [];
        foreach ($filterGroups as $filterGroup) {
            $countryFilters = $this->getCountryFilters($filterGroup);

            if (count($countryFilters) > 1) {
                // This group has multiple country filters
                // Create a special combined filter
                $combinedFilter = $this->createCombinedCountryFilter($countryFilters);

                // Get other filters (non-country filters)
                $otherFilters = [];
                foreach ($filterGroup->getFilters() as $filter) {
                    if ($filter->getField() !== self::COUNTRY_FIELD) {
                        $otherFilters[] = $filter;
                    }
                }

                // Add the combined filter
                $otherFilters[] = $combinedFilter;

                // Create new filter group
                $newGroup = clone $filterGroup;
                $newGroup->setFilters($otherFilters);
                $modifiedGroups[] = $newGroup;
            } else {
                // No multiple country filters, keep as is
                $modifiedGroups[] = $filterGroup;
            }
        }

        // Create new SearchCriteria with modified filter groups
        $newSearchCriteria = clone $searchCriteria;
        $newSearchCriteria->setFilterGroups($modifiedGroups);

        return $newSearchCriteria;
    }

    /**
     * Get all country_of_manufacture filters from a filter group
     *
     * @param FilterGroup $filterGroup
     * @return Filter[]
     */
    private function getCountryFilters(FilterGroup $filterGroup): array
    {
        $countryFilters = [];
        foreach ($filterGroup->getFilters() as $filter) {
            if ($filter->getField() === self::COUNTRY_FIELD) {
                $countryFilters[] = $filter;
            }
        }
        return $countryFilters;
    }

    /**
     * Create a combined country filter using 'in' condition type with all values
     * This will be processed by our custom ProductCountryFilter
     *
     * @param Filter[] $filters
     * @return Filter
     */
    private function createCombinedCountryFilter(array $filters): Filter
    {
        $values = [];
        $hasGlobal = false;
        $specificCountries = [];

        // Collect all values
        foreach ($filters as $filter) {
            $value = $filter->getValue();
            if (strtolower($value) === self::GLOBAL_FILTER_VALUE) {
                $hasGlobal = true;
            } else {
                $specificCountries[] = $value;
            }
        }

        // Build combined value
        // We'll use a special format: "global,KR,JP" which our custom filter will parse
        if ($hasGlobal) {
            $values[] = self::GLOBAL_FILTER_VALUE;
        }
        $values = array_merge($values, $specificCountries);

        // Create a new filter with combined values
        $filter = new \Magento\Framework\Api\Filter();
        $filter->setField(self::COUNTRY_FIELD);
        $filter->setValue(implode(',', $values));
        $filter->setConditionType('in'); // We'll use 'in' to indicate multiple values

        return $filter;
    }
}
