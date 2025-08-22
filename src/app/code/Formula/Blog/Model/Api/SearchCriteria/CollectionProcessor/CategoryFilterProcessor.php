<?php
/**
 * Category Filter Processor for Blog Collection
 *
 * @category  Formula
 * @package   Formula\Blog
 */
namespace Formula\Blog\Model\Api\SearchCriteria\CollectionProcessor;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\AbstractDb;

class CategoryFilterProcessor implements CollectionProcessorInterface
{
    /**
     * @inheritdoc
     */
    public function process(SearchCriteriaInterface $searchCriteria, AbstractDb $collection)
    {
        $filterGroups = $searchCriteria->getFilterGroups();
        
        foreach ($filterGroups as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $fieldName = $filter->getField();
                
                // Check if this is a category field
                if (in_array($fieldName, ['categoryId', 'categoryIds', 'category_id', 'category_ids'])) {
                    $categoryIds = $filter->getValue();
                    $conditionType = $filter->getConditionType();
                    
                    // Debug logging
                    error_log("CategoryFilterProcessor: Processing field={$fieldName}, value=" . print_r($categoryIds, true) . ", conditionType={$conditionType}");
                    
                    if (is_string($categoryIds)) {
                        $categoryIds = explode(',', $categoryIds);
                    }
                    
                    if (is_array($categoryIds) && !empty($categoryIds)) {
                        $this->addCategoryFilter($collection, $categoryIds, $conditionType);
                    }
                }
            }
        }
    }
    
    /**
     * Add category filter to collection
     *
     * @param AbstractDb $collection
     * @param array $categoryIds
     * @param string $conditionType
     * @return void
     */
    private function addCategoryFilter($collection, array $categoryIds, $conditionType = 'eq')
    {
        $conditions = [];
        
        foreach ($categoryIds as $categoryId) {
            $conditions[] = "JSON_CONTAINS(category_ids, '\"" . (int)$categoryId . "\"')";
        }
        
        if (!empty($conditions)) {
            $whereClause = implode(' OR ', $conditions);
            
            // Debug logging
            error_log("CategoryFilterProcessor: Applying filter with whereClause={$whereClause}");
            
            // Handle different condition types
            switch ($conditionType) {
                case 'in':
                    // For 'in' condition, we want blogs that contain ANY of the specified categories
                    $collection->getSelect()->where($whereClause);
                    break;
                case 'eq':
                default:
                    // For 'eq' condition, we want blogs that contain ALL of the specified categories
                    // This is more restrictive - requires all categories to be present
                    if (count($categoryIds) === 1) {
                        $collection->getSelect()->where($whereClause);
                    } else {
                        // For multiple categories with 'eq', we need all categories to be present
                        $allConditions = [];
                        foreach ($categoryIds as $categoryId) {

                            $allConditions[] = "JSON_CONTAINS(category_ids, '\"" . (int)$categoryId . "\"')";
                        }
                        $finalWhereClause = implode(' AND ', $allConditions);
                        error_log("CategoryFilterProcessor: Multiple categories with eq: finalWhereClause={$finalWhereClause}");
                        $collection->getSelect()->where($finalWhereClause);
                    }
                    break;
            }
        }
    }
}
