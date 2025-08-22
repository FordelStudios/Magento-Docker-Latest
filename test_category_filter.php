<?php
/**
 * Test script for Blog category filtering
 * 
 * This script tests the category filtering functionality
 */

require_once 'src/app/bootstrap.php';

use Magento\Framework\App\Bootstrap;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Formula\Blog\Api\BlogRepositoryInterface;

try {
    // Bootstrap Magento
    $bootstrap = Bootstrap::create(BP, $_SERVER);
    $objectManager = $bootstrap->getObjectManager();
    
    // Get the blog repository
    $blogRepository = $objectManager->get(BlogRepositoryInterface::class);
    
    // Create search criteria builder
    $searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
    $filterBuilder = $objectManager->get(FilterBuilder::class);
    $filterGroupBuilder = $objectManager->get(FilterGroupBuilder::class);
    
    echo "=== Blog Category Filtering Test ===\n\n";
    
    // Test 1: Filter by category_id = 5
    echo "1. Filtering blogs by category_id = 5:\n";
    $filter = $filterBuilder
        ->setField('category_id')
        ->setValue('5')
        ->setConditionType('in')
        ->create();
    
    $filterGroup = $filterGroupBuilder->addFilter($filter)->create();
    $searchCriteria = $searchCriteriaBuilder->setFilterGroups([$filterGroup])->create();
    $blogs = $blogRepository->getList($searchCriteria);
    echo "   Blogs with category_id 5: " . $blogs->getTotalCount() . "\n";
    
    foreach ($blogs->getItems() as $blog) {
        echo "   - Blog ID: " . $blog['id'] . ", Title: " . $blog['title'] . ", Categories: " . implode(', ', $blog['category_ids']) . "\n";
    }
    
    // Test 2: Filter by category_id = 3
    echo "\n2. Filtering blogs by category_id = 3:\n";
    $filter = $filterBuilder
        ->setField('category_id')
        ->setValue('3')
        ->setConditionType('in')
        ->create();
    
    $filterGroup = $filterGroupBuilder->addFilter($filter)->create();
    $searchCriteria = $searchCriteriaBuilder->setFilterGroups([$filterGroup])->create();
    $blogs = $blogRepository->getList($searchCriteria);
    echo "   Blogs with category_id 3: " . $blogs->getTotalCount() . "\n";
    
    foreach ($blogs->getItems() as $blog) {
        echo "   - Blog ID: " . $blog['id'] . ", Title: " . $blog['title'] . ", Categories: " . implode(', ', $blog['category_ids']) . "\n";
    }
    
    // Test 3: Filter by multiple category_ids (3, 5)
    echo "\n3. Filtering blogs by category_ids = [3, 5]:\n";
    $filter = $filterBuilder
        ->setField('category_ids')
        ->setValue('3,5')
        ->setConditionType('in')
        ->create();
    
    $filterGroup = $filterGroupBuilder->addFilter($filter)->create();
    $searchCriteria = $searchCriteriaBuilder->setFilterGroups([$filterGroup])->create();
    $blogs = $blogRepository->getList($searchCriteria);
    echo "   Blogs with category_ids 3 or 5: " . $blogs->getTotalCount() . "\n";
    
    foreach ($blogs->getItems() as $blog) {
        echo "   - Blog ID: " . $blog['id'] . ", Title: " . $blog['title'] . ", Categories: " . implode(', ', $blog['category_ids']) . "\n";
    }
    
    echo "\n=== Test completed ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
