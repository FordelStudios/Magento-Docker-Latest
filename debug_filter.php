<?php
// Debug script to test the filter processing
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

// Create search criteria manually to test
$searchCriteriaBuilder = $objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
$filterBuilder = $objectManager->create(\Magento\Framework\Api\FilterBuilder::class);

// Create filter for category_id = 5
$filter = $filterBuilder
    ->setField('category_id')
    ->setValue('5')
    ->setConditionType('in')
    ->create();

$searchCriteria = $searchCriteriaBuilder
    ->addFilter($filter)
    ->setPageSize(10)
    ->create();

// Get blog repository
$blogRepository = $objectManager->create(\Formula\Blog\Api\BlogRepositoryInterface::class);

echo "Testing manual search criteria...\n";
echo "Filter groups count: " . count($searchCriteria->getFilterGroups()) . "\n";

foreach ($searchCriteria->getFilterGroups() as $i => $filterGroup) {
    echo "Filter group $i:\n";
    foreach ($filterGroup->getFilters() as $j => $filter) {
        echo "  Filter [$j]: field=" . $filter->getField() . ", value=" . $filter->getValue() . ", condition=" . $filter->getConditionType() . "\n";
    }
}

// Test the repository
try {
    $results = $blogRepository->getList($searchCriteria);
    echo "Results found: " . $results->getTotalCount() . "\n";
    echo "Items:\n";
    foreach ($results->getItems() as $item) {
        echo "  - ID: " . $item['id'] . ", Title: " . $item['title'] . ", Categories: " . json_encode($item['category_ids']) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}