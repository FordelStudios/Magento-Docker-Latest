<?php
/**
 * Blog Collection Processor
 * Combines category filtering with standard search criteria processing
 *
 * @category  Formula
 * @package   Formula\Blog
 */
namespace Formula\Blog\Model\Api\SearchCriteria;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Formula\Blog\Model\Api\SearchCriteria\CollectionProcessor\CategoryFilterProcessor;
use Formula\Blog\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\SortingProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\PaginationProcessor;

class BlogCollectionProcessor implements CollectionProcessorInterface
{
    /**
     * @var CategoryFilterProcessor
     */
    private $categoryFilterProcessor;
    
    /**
     * @var FilterProcessor
     */
    private $filterProcessor;
    
    /**
     * @var SortingProcessor
     */
    private $sortingProcessor;
    
    /**
     * @var PaginationProcessor
     */
    private $paginationProcessor;

    /**
     * @param CategoryFilterProcessor $categoryFilterProcessor
     * @param FilterProcessor $filterProcessor
     * @param SortingProcessor $sortingProcessor
     * @param PaginationProcessor $paginationProcessor
     */
    public function __construct(
        CategoryFilterProcessor $categoryFilterProcessor,
        FilterProcessor $filterProcessor,
        SortingProcessor $sortingProcessor,
        PaginationProcessor $paginationProcessor
    ) {
        $this->categoryFilterProcessor = $categoryFilterProcessor;
        $this->filterProcessor = $filterProcessor;
        $this->sortingProcessor = $sortingProcessor;
        $this->paginationProcessor = $paginationProcessor;
    }

    /**
     * @inheritdoc
     */
    public function process(SearchCriteriaInterface $searchCriteria, AbstractDb $collection)
    {
        // Debug logging
        error_log("BlogCollectionProcessor: Processing search criteria with " . count($searchCriteria->getFilterGroups()) . " filter groups");
        
        // Log all filter groups for debugging
        foreach ($searchCriteria->getFilterGroups() as $i => $filterGroup) {
            foreach ($filterGroup->getFilters() as $j => $filter) {
                error_log("BlogCollectionProcessor: Filter [{$i}][{$j}]: field=" . $filter->getField() . ", value=" . $filter->getValue() . ", condition=" . $filter->getConditionType());
            }
        }
        
        // First, apply category filtering (handles category fields)
        $this->categoryFilterProcessor->process($searchCriteria, $collection);
        
        // Then, apply standard filtering (excludes category fields)
        $this->filterProcessor->process($searchCriteria, $collection);
        
        // Apply sorting
        $this->sortingProcessor->process($searchCriteria, $collection);
        
        // Apply pagination
        $this->paginationProcessor->process($searchCriteria, $collection);
        
        // Debug final collection
        error_log("BlogCollectionProcessor: Final collection size: " . $collection->getSize());
        error_log("BlogCollectionProcessor: SQL: " . $collection->getSelect()->__toString());
    }
}
