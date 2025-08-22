<?php
/**
 * Filter Processor for Blog Collection
 * Removes category fields from search criteria before standard processing
 *
 * @category  Formula
 * @package   Formula\Blog
 */
namespace Formula\Blog\Model\Api\SearchCriteria\CollectionProcessor;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor as BaseFilterProcessor;

class FilterProcessor implements CollectionProcessorInterface
{
    /**
     * @var BaseFilterProcessor
     */
    private $baseFilterProcessor;

    /**
     * @param BaseFilterProcessor $baseFilterProcessor
     */
    public function __construct(
        BaseFilterProcessor $baseFilterProcessor
    ) {
        $this->baseFilterProcessor = $baseFilterProcessor;
    }

    /**
     * @inheritdoc
     */
    public function process(SearchCriteriaInterface $searchCriteria, AbstractDb $collection)
    {
        // Create a new search criteria without category fields
        $filteredSearchCriteria = $this->removeCategoryFields($searchCriteria);
        
        // Process with the base filter processor
        $this->baseFilterProcessor->process($filteredSearchCriteria, $collection);
    }
    
    /**
     * Remove category fields from search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchCriteriaInterface
     */
    private function removeCategoryFields(SearchCriteriaInterface $searchCriteria)
    {
        $filterGroups = $searchCriteria->getFilterGroups();
        $filteredFilterGroups = [];
        
        foreach ($filterGroups as $filterGroup) {
            $filteredFilters = [];
            $hasCategoryFilter = false;
            
            foreach ($filterGroup->getFilters() as $filter) {
                $fieldName = $filter->getField();
                
                // Check if this is a category field
                if (in_array($fieldName, ['categoryId', 'categoryIds', 'category_id', 'category_ids'])) {
                    $hasCategoryFilter = true;
                    // Skip category fields - they will be handled by CategoryFilterProcessor
                    error_log("FilterProcessor: Skipping category field: {$fieldName}");
                } else {
                    $filteredFilters[] = $filter;
                }
            }
            
            // Only add filter group if it has non-category filters
            if (!empty($filteredFilters)) {
                // Create a new filter group with the filtered filters
                $newFilterGroup = clone $filterGroup;
                $newFilterGroup->setFilters($filteredFilters);
                $filteredFilterGroups[] = $newFilterGroup;
            }
            
            if ($hasCategoryFilter) {
                error_log("FilterProcessor: Removed filter group with category filters");
            }
        }
        
        // Clone search criteria and set filtered filter groups
        $newSearchCriteria = clone $searchCriteria;
        $newSearchCriteria->setFilterGroups($filteredFilterGroups);
        
        error_log("FilterProcessor: Original filter groups: " . count($filterGroups) . ", Filtered: " . count($filteredFilterGroups));
        
        return $newSearchCriteria;
    }
}
