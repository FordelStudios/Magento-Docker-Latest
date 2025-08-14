<?php
/**
 * Test script to verify the new product ID endpoint
 * Run this from your Magento root directory
 */

require_once 'app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

// Test with the known product ID from your database
$testProductId = 21; // This is the product ID for "Hydro Boost??Hyaluronic Acid Serum (30 ml)"
$testSku = "Hydro Boost® Hyaluronic Acid Serum (30 ml)";

echo "Testing Product ID Endpoint\n";
echo "===========================\n\n";

echo "Product ID: $testProductId\n";
echo "Expected Product: Hydro Boost??Hyaluronic Acid Serum (30 ml)\n";
echo "Test SKU: $testSku\n\n";

try {
    // Test the review repository
    $reviewRepository = $objectManager->get(\Formula\Review\Api\ProductReviewRepositoryInterface::class);
    
    echo "1. Testing if the new methods exist...\n";
    try {
        $reflection = new ReflectionClass($reviewRepository);
        
        $method1 = $reflection->getMethod('getCustomerExistingReviewByProductId');
        echo "✓ getCustomerExistingReviewByProductId method exists\n";
        
        $method2 = $reflection->getMethod('getProductIdBySku');
        echo "✓ getProductIdBySku method exists\n";
        
    } catch (\Exception $e) {
        echo "✗ Method not found: " . $e->getMessage() . "\n";
        exit;
    }
    
    echo "\n2. Testing product lookup by ID...\n";
    try {
        $productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $product = $productRepository->getById($testProductId);
        echo "✓ Found product:\n";
        echo "  Product ID: " . $product->getId() . "\n";
        echo "  Product SKU: " . $product->getSku() . "\n";
        echo "  Product Name: " . $product->getName() . "\n";
    } catch (\Exception $e) {
        echo "✗ Failed to find product: " . $e->getMessage() . "\n";
        exit;
    }
    
    echo "\n3. Testing the new methods directly...\n";
    try {
        $repository = $objectManager->get(\Formula\Review\Model\ProductReviewRepository::class);
        $reflection = new ReflectionClass($repository);
        
        // Test getProductIdBySku
        $skuMethod = $reflection->getMethod('getProductIdBySku');
        $skuMethod->setAccessible(true);
        
        $foundProductId = $skuMethod->invoke($repository, $testSku);
        echo "✓ getProductIdBySku returned: $foundProductId\n";
        
        // Test getCustomerExistingReviewByProductId
        $productIdMethod = $reflection->getMethod('getCustomerExistingReviewByProductId');
        $productIdMethod->setAccessible(true);
        
        echo "✓ getCustomerExistingReviewByProductId method is accessible\n";
        
        // Test the getExistingReviewId method
        $existingReviewMethod = $reflection->getMethod('getExistingReviewId');
        $existingReviewMethod->setAccessible(true);
        
        // Test with customer ID 13 (from your database)
        $reviewId = $existingReviewMethod->invoke($repository, 13, $testProductId);
        if ($reviewId) {
            echo "✓ Found existing review for customer 13:\n";
            echo "  Review ID: $reviewId\n";
        } else {
            echo "✗ No existing review found for customer 13\n";
        }
        
    } catch (\Exception $e) {
        echo "✗ Test failed: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n===========================\n";
echo "Test completed.\n";
echo "\nTo test the actual API endpoints, use:\n";
echo "\n1. Check existing review by Product ID:\n";
echo "curl \"https://admin.theformulashop.com/rest/V1/reviews/product-id/$testProductId\" \\\n";
echo "     -H \"Authorization: Bearer YOUR_TOKEN\"\n";
echo "\n2. Get Product ID from SKU:\n";
echo "curl \"https://admin.theformulashop.com/rest/V1/reviews/sku-to-product-id/Hydro%20Boost%C2%AE%C2%A0Hyaluronic%20Acid%20Serum%20(30%20ml)\" \\\n";
echo "     -H \"Authorization: Bearer YOUR_TOKEN\"\n";
echo "\n3. Check existing review by SKU (original method):\n";
echo "curl \"https://admin.theformulashop.com/rest/V1/reviews/product/Hydro%20Boost%C2%AE%C2%A0Hyaluronic%20Acid%20Serum%20(30%20ml)\" \\\n";
echo "     -H \"Authorization: Bearer YOUR_TOKEN\"\n";
