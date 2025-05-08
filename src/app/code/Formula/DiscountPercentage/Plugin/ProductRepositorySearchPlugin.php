<?php
namespace Formula\DiscountPercentage\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

class ProductRepositorySearchPlugin
{
    /**
     * @var array
     */
    private $extFilters = [];

    /**
     * Process extension attribute filter
     *
     * @param ProductRepositoryInterface $subject
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function beforeGetList(
        ProductRepositoryInterface $subject,
        SearchCriteriaInterface $searchCriteria
    ) {
        $this->extFilters     = [];
        $standardFilterGroups = [];

        // Check if we have discount_percentage filters
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $discountFilters = [];
            $standardFilters = [];

            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'discount_percentage') {
                    $discountFilters[]  = $filter;
                    $this->extFilters[] = $filter;
                } else {
                    $standardFilters[] = $filter;
                }
            }

            if (! empty($standardFilters)) {
                // Create a new filter group with only standard filters
                $newFilterGroup = clone $filterGroup;
                $newFilterGroup->setFilters($standardFilters);
                $standardFilterGroups[] = $newFilterGroup;
            }
        }

        if (! empty($this->extFilters)) {
            // Replace filter groups with only the standard ones
            $searchCriteria->setFilterGroups($standardFilterGroups);
        }

        return [$searchCriteria];
    }

    /**
     * Post-process discount_percentage filters
     *
     * @param ProductRepositoryInterface $subject
     * @param \Magento\Catalog\Api\Data\ProductSearchResultsInterface $result
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Catalog\Api\Data\ProductSearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        \Magento\Catalog\Api\Data\ProductSearchResultsInterface $result,
        SearchCriteriaInterface $searchCriteria
    ) {
        if (empty($this->extFilters)) {
            return $result;
        }

        $filteredItems = [];

        foreach ($result->getItems() as $item) {
            if ($this->passesFilters($item)) {
                $filteredItems[] = $item;
            }
        }

        $result->setItems($filteredItems);
        $result->setTotalCount(count($filteredItems));

        return $result;
    }

    /**
     * Check if a product passes all discount_percentage filters
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return bool
     */
    private function passesFilters($product)
    {
        $extensions = $product->getExtensionAttributes();
        if (! $extensions) {
            return false;
        }

        $discountPercentage = $extensions->getDiscountPercentage();

        foreach ($this->extFilters as $filter) {
            $value  = (float) $filter->getValue();
            $actual = (float) $discountPercentage;

            switch ($filter->getConditionType()) {
                case 'eq':
                    if ($actual != $value) {
                        return false;
                    }
                    break;
                case 'neq':
                    if ($actual == $value) {
                        return false;
                    }
                    break;
                case 'gt':
                    if ($actual <= $value) {
                        return false;
                    }
                    break;
                case 'lt':
                    if ($actual >= $value) {
                        return false;
                    }
                    break;
                case 'gteq':
                    if ($actual < $value) {
                        return false;
                    }
                    break;
                case 'lteq':
                    if ($actual > $value) {
                        return false;
                    }
                    break;
                default:
                    return false;
            }
        }

        return true;
    }
}
