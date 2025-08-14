<?php
/**
 * Test script to debug SKU matching issues
 * Run this from your Magento root directory
 */

require_once 'app/bootstrap.php';

$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

// Test SKU with special characters
$testSku = "Hydro Boost® Hyaluronic Acid Serum (30 ml)";
$encodedSku = urlencode($testSku);

echo "Original SKU: " . $testSku . "\n";
echo "URL Encoded: " . $encodedSku . "\n";
echo "URL Decoded: " . urldecode($encodedSku) . "\n\n";

try {
    // Try to get product by original SKU
    $productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    
    echo "Testing product lookup...\n";
    
    try {
        $product = $productRepository->get($testSku);
        echo "✓ Found product with original SKU:\n";
        echo "  ID: " . $product->getId() . "\n";
        echo "  SKU: " . $product->getSku() . "\n";
    } catch (\Exception $e) {
        echo "✗ Failed to find product with original SKU: " . $e->getMessage() . "\n";
    }
    
    try {
        $product = $productRepository->get($encodedSku);
        echo "✓ Found product with encoded SKU:\n";
        echo "  ID: " . $product->getId() . "\n";
        echo "  SKU: " . $product->getSku() . "\n";
    } catch (\Exception $e) {
        echo "✗ Failed to find product with encoded SKU: " . $e->getMessage() . "\n";
    }
    
    try {
        $product = $productRepository->get(urldecode($encodedSku));
        echo "✓ Found product with decoded SKU:\n";
        echo "  ID: " . $product->getId() . "\n";
        echo "  SKU: " . $product->getSku() . "\n";
    } catch (\Exception $e) {
        echo "✗ Failed to find product with decoded SKU: " . $e->getMessage() . "\n";
    }
    
    // Test the review repository
    echo "\nTesting review repository...\n";
    
    $reviewRepository = $objectManager->get(\Formula\Review\Api\ProductReviewRepositoryInterface::class);
    
    try {
        $result = $reviewRepository->debugSkuMatching($testSku);
        echo "Debug result:\n";
        print_r($result);
    } catch (\Exception $e) {
        echo "✗ Debug method failed: " . $e->getMessage() . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
