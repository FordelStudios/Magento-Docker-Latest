<?php
/**
 * Test script to verify SKU matching fix
 * Run this from your Magento root directory
 */

require_once 'app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

// Test the problematic SKU
$testSku = "Hydro Boost® Hyaluronic Acid Serum (30 ml)";

echo "Testing SKU: " . $testSku . "\n";
echo "=====================================\n\n";

try {
    // Test the review repository
    $reviewRepository = $objectManager->get(\Formula\Review\Api\ProductReviewRepositoryInterface::class);
    
    echo "1. Testing debugSkuMatching method...\n";
    try {
        $result = $reviewRepository->debugSkuMatching($testSku);
        echo "Debug result:\n";
        print_r($result);
    } catch (\Exception $e) {
        echo "✗ Debug method failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n2. Testing getCustomerExistingReview method...\n";
    try {
        // Note: This will require authentication, so we'll just test if the method exists
        $reflection = new ReflectionClass($reviewRepository);
        $method = $reflection->getMethod('getCustomerExistingReview');
        echo "✓ Method exists and is accessible\n";
        
        // Test the normalization logic directly
        $repository = $objectManager->get(\Formula\Review\Model\ProductReviewRepository::class);
        $reflection = new ReflectionClass($repository);
        $normalizeMethod = $reflection->getMethod('normalizeSku');
        $normalizeMethod->setAccessible(true);
        
        $normalizedSku = $normalizeMethod->invoke($repository, $testSku);
        echo "Normalized SKU: " . $normalizedSku . "\n";
        
        // Test multiple variations
        $variationsMethod = $reflection->getMethod('findProductByMultipleSkuVariations');
        $variationsMethod->setAccessible(true);
        
        $product = $variationsMethod->invoke($repository, $testSku);
        if ($product) {
            echo "✓ Found product with multiple SKU variations:\n";
            echo "  Product ID: " . $product->getId() . "\n";
            echo "  Product SKU: " . $product->getSku() . "\n";
        } else {
            echo "✗ No product found with multiple SKU variations\n";
        }
        
    } catch (\Exception $e) {
        echo "✗ Test failed: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "Test completed.\n";
