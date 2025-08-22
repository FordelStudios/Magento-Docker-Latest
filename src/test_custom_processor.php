<?php
/**
 * Test custom collection processor for blog category filtering
 */

require_once 'app/bootstrap.php';

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
    
    echo "=== Testing Custom Collection Processor ===\n\n";
    
    // Test 1: Get all blogs (baseline)
    echo "1. Getting all blogs (baseline):\n";
    $searchCriteria = $searchCriteriaBuilder->create();
    $blogs = $blogRepository->getList($searchCriteria);
    echo "   Total blogs found: " . $blogs->getTotalCount() . "\n";
    
    foreach ($blogs->getItems() as $blog) {
        echo "   - Blog ID: " . $blog['id'] . ", Title: " . $blog['title'] . ", Categories: " . json_encode($blog['category_ids']) . "\n";
    }
    
    // Reset the search criteria builder
    $searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
    $filterGroupBuilder = $objectManager->get(FilterGroupBuilder::class);
    
    // Test 2: Filter by category_id = 5
    echo "\n2. Filtering blogs by category_id = 5:\n";
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
        echo "   - Blog ID: " . $blog['id'] . ", Title: " . $blog['title'] . ", Categories: " . json_encode($blog['category_ids']) . "\n";
    }
    
    // Reset the search criteria builder and filter group builder
    $searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
    $filterGroupBuilder = $objectManager->get(FilterGroupBuilder::class);
    
    // Test 3: Filter by category_id = 3
    echo "\n3. Filtering blogs by category_id = 3:\n";
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
        echo "   - Blog ID: " . $blog['id'] . ", Title: " . $blog['title'] . ", Categories: " . json_encode($blog['category_ids']) . "\n";
    }
    
    // Reset the search criteria builder and filter group builder
    $searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
    $filterGroupBuilder = $objectManager->get(FilterGroupBuilder::class);
    
    // Test 4: Filter by multiple category_ids
    echo "\n4. Filtering blogs by category_ids = [3, 5]:\n";
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
        echo "   - Blog ID: " . $blog['id'] . ", Title: " . $blog['title'] . ", Categories: " . json_encode($blog['category_ids']) . "\n";
    }
    
    echo "\n=== Test completed ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
