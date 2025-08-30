<?php
/**
 * Test script to verify HomeContent listing modal functionality
 */

require_once 'src/app/bootstrap.php';

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\ObjectManager;

try {
    $bootstrap = Bootstrap::create(BP, $_SERVER);
    $objectManager = $bootstrap->getObjectManager();
    
    // Get the HomeContent collection
    $homeContentCollection = $objectManager->create(\Formula\HomeContent\Model\ResourceModel\HomeContent\Collection::class);
    
    echo "HomeContent Collection Test\n";
    echo "==========================\n";
    echo "Total records: " . $homeContentCollection->getSize() . "\n\n";
    
    // Get first record to test data structure
    $firstRecord = $homeContentCollection->getFirstItem();
    if ($firstRecord->getId()) {
        echo "First Record Details:\n";
        echo "ID: " . $firstRecord->getId() . "\n";
        echo "Active: " . ($firstRecord->getActive() ? 'Yes' : 'No') . "\n";
        echo "Hero Banners: " . $firstRecord->getHeroBanners() . "\n";
        echo "5-Step Routine Banner: " . $firstRecord->getFiveStepRoutineBanner() . "\n";
        echo "3-Step Routine Banner: " . $firstRecord->getThreeStepRoutineBanner() . "\n";
        echo "Discover Your Formula Banner: " . $firstRecord->getDiscoverYourFormulaBanner() . "\n";
        echo "Korean Formula Banner: " . $firstRecord->getBestOfKoreanFormulaBanner() . "\n";
        echo "Perfect Gift Image: " . $firstRecord->getPerfectGiftImage() . "\n";
        echo "Bottom Banner: " . $firstRecord->getBottomBanner() . "\n";
        
        // Test URL generation
        $urlBuilder = $objectManager->create(\Magento\Framework\UrlInterface::class);
        $editUrl = $urlBuilder->getUrl('formula_homecontent/homecontent/edit', ['id' => $firstRecord->getId()]);
        echo "\nEdit URL: " . $editUrl . "\n";
        
    } else {
        echo "No records found in the collection.\n";
    }
    
    echo "\nTest completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
