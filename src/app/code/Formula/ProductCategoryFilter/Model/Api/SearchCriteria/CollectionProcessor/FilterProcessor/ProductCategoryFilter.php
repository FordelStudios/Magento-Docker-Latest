<?php
/**
 * Copyright © Formula. All rights reserved.
 *
 * Custom filter processor for category_id attribute
 * Fixes pagination/total_count issues when using IN condition with multiple categories
 */
namespace Formula\ProductCategoryFilter\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\ObjectManager;

/**
 * Custom filter processor for category_id attribute
 * Uses subquery approach to avoid duplicate counting issues with pagination
 */
class ProductCategoryFilter implements CustomFilterInterface
{
    /**
     * Apply category_id Filter to Product Collection
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

        // Parse comma-separated category IDs
        $categoryIds = $this->parseCategoryIds($value);

        if (empty($categoryIds)) {
            return false;
        }

        // Apply category filter using subquery to avoid duplicate counting
        $this->applyCategoryFilter($collection, $categoryIds, $conditionType);

        return true;
    }

    /**
     * Parse category IDs from value
     *
     * @param mixed $value
     * @return array
     */
    private function parseCategoryIds($value): array
    {
        if (is_array($value)) {
            return array_map('intval', $value);
        }

        if (is_string($value) && strpos($value, ',') !== false) {
            return array_map('intval', explode(',', $value));
        }

        if (is_numeric($value)) {
            return [(int) $value];
        }

        return [];
    }

    /**
     * Apply category filter using subquery approach
     * This avoids the JOIN duplicate issue that causes incorrect total_count
     *
     * @param Collection $collection
     * @param array $categoryIds
     * @param string $conditionType
     * @return void
     */
    private function applyCategoryFilter(Collection $collection, array $categoryIds, string $conditionType): void
    {
        $resourceConnection = ObjectManager::getInstance()->get(\Magento\Framework\App\ResourceConnection::class);
        $categoryProductTable = $resourceConnection->getTableName('catalog_category_product');

        if ($conditionType === 'in' || $conditionType === 'eq' || count($categoryIds) > 1) {
            // Create subquery SQL string directly
            $categoryIdsStr = implode(',', array_map('intval', $categoryIds));
            $subQuerySql = "SELECT DISTINCT product_id FROM {$categoryProductTable} WHERE category_id IN ({$categoryIdsStr})";

            // Use addFieldToFilter with a DB expression for the subquery
            $collection->addFieldToFilter(
                'entity_id',
                ['in' => new \Zend_Db_Expr("({$subQuerySql})")]
            );
        } elseif ($conditionType === 'nin') {
            // Create subquery SQL string directly
            $categoryIdsStr = implode(',', array_map('intval', $categoryIds));
            $subQuerySql = "SELECT DISTINCT product_id FROM {$categoryProductTable} WHERE category_id IN ({$categoryIdsStr})";

            // Use addFieldToFilter with NOT IN
            $collection->addFieldToFilter(
                'entity_id',
                ['nin' => new \Zend_Db_Expr("({$subQuerySql})")]
            );
        }
    }
}
