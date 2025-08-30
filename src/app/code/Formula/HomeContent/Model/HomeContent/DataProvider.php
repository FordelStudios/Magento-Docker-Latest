<?php
declare(strict_types=1);

namespace Formula\HomeContent\Model\HomeContent;

use Formula\HomeContent\Model\ResourceModel\HomeContent\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class DataProvider extends AbstractDataProvider
{
    protected $collection;
    protected $loadedData;
    protected $storeManager;
    protected $logger;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        
        // Debug: Log collection setup
        $this->logger->debug('DataProvider - Collection created with name: ' . $name);
    }

    public function getData()
    {
        $this->logger->debug('DataProvider - getData() method called');
        
        if (isset($this->loadedData)) {
            $this->logger->debug('DataProvider - Returning cached loaded data');
            return $this->loadedData;
        }

        // Debug: Log collection details before loading
        $this->logger->debug('DataProvider - Collection SQL: ' . $this->collection->getSelect()->__toString());
        $this->logger->debug('DataProvider - Collection count: ' . $this->collection->getSize());
        
        $items = $this->collection->getItems();
        $this->logger->debug('DataProvider - Items loaded: ' . count($items));
        
        $this->loadedData = [];

        $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        foreach ($items as $item) {
            $entityId = $item->getEntityId();
            $this->logger->debug("DataProvider - Processing entity ID: $entityId");
            
            $data = $item->getData();
            
            // Debug: Log the raw data from database
            $this->logger->debug('DataProvider - Raw data for entity ' . $entityId . ': ' . json_encode($data));
            
            // Debug: Check active field specifically
            if (isset($data['active'])) {
                $this->logger->debug("DataProvider - Active field found in raw data: " . var_export($data['active'], true));
                $this->logger->debug("DataProvider - Active field type: " . gettype($data['active']));
            } else {
                $this->logger->debug("DataProvider - Active field NOT found in raw data");
            }
            
            $imageFields = [
                'five_step_routine_banner',
                'three_step_routine_banner',
                'discover_your_formula_banner',
                'best_of_korean_formula_banner',
                'perfect_gift_image',
                'bottom_banner'
            ];

            foreach ($imageFields as $field) {
                if (isset($data[$field]) && $data[$field]) {
                    $imagePath = $data[$field];
                    
                    // Check if the image exists in the final location
                    $finalPath = 'formula/homecontent/' . $imagePath;
                    $tmpPath = 'formula/tmp/homecontent/' . $imagePath;
                    
                    // Determine which path to use based on file existence
                    $usePath = $finalPath;
                    if (!$this->fileExists($finalPath) && $this->fileExists($tmpPath)) {
                        $usePath = $tmpPath;
                    }
                    
                    $data[$field] = [
                        [
                            'name' => basename($imagePath),
                            'url' => $baseUrl . $usePath,
                            'size' => $this->getFileSize($usePath),
                            'type' => 'image'
                        ]
                    ];
                    
                    // Debug: Log image field processing
                    $this->logger->debug("DataProvider - Field: $field, Path: $usePath, URL: " . $baseUrl . $usePath);
                }
            }

            if (isset($data['hero_banners']) && $data['hero_banners']) {
                $heroBanners = json_decode($data['hero_banners'], true);
                $heroData = [];
                foreach ($heroBanners as $banner) {
                    if ($banner) {
                        $finalPath = 'formula/homecontent/' . $banner;
                        $tmpPath = 'formula/tmp/homecontent/' . $banner;
                        
                        $usePath = $finalPath;
                        if (!$this->fileExists($finalPath) && $this->fileExists($tmpPath)) {
                            $usePath = $tmpPath;
                        }
                        
                        $heroData[] = [
                            'name' => basename($banner),
                            'url' => $baseUrl . $usePath,
                            'size' => $this->getFileSize($usePath),
                            'type' => 'image'
                        ];
                    }
                }
                $data['hero_banners'] = $heroData;
            }

            if (isset($data['discover_korean_ingredients_banners']) && $data['discover_korean_ingredients_banners']) {
                $koreanBanners = json_decode($data['discover_korean_ingredients_banners'], true);
                if (is_array($koreanBanners)) {
                    $koreanData = [];
                    foreach ($koreanBanners as $banner) {
                        if (isset($banner['image']) && isset($banner['ingredientId'])) {
                            $finalPath = 'formula/homecontent/' . $banner['image'];
                            $tmpPath = 'formula/tmp/homecontent/' . $banner['image'];
                            
                            $usePath = $finalPath;
                            if (!$this->fileExists($finalPath) && $this->fileExists($tmpPath)) {
                                $usePath = $tmpPath;
                            }
                            
                            $koreanData[] = [
                                'image' => $baseUrl . $usePath,
                                'ingredient_id' => $banner['ingredientId']
                            ];
                        }
                    }
                    $data['discover_korean_ingredients_banners'] = $koreanData;
                }
            }

            // Debug: Log active field before processing
            $this->logger->debug("DataProvider - Before processing active field: " . var_export($data['active'] ?? 'NOT_SET', true));
            
            // Ensure active field is properly handled for form (0/1 values)
            if (isset($data['active'])) {
                $originalActive = $data['active'];
                $data['active'] = (int)$data['active'];
                $this->logger->debug("DataProvider - Active field processed: original='$originalActive', converted to int=" . $data['active']);
            } else {
                $data['active'] = 0; // Default value (matches db_schema.xml)
                $this->logger->debug("DataProvider - Active field not found, setting default to 0");
            }
            
            // Debug: Log the active value being loaded
            $this->logger->debug('DataProvider - Entity ID: ' . $entityId . ', Final active value: ' . $data['active'] . ' (type: ' . gettype($data['active']) . ')');
            
            // Debug: Log the complete processed data for this entity
            $this->logger->debug('DataProvider - Complete processed data for entity ' . $entityId . ': ' . json_encode($data));

            $this->loadedData[$entityId] = $data;
        }

        if (empty($this->loadedData)) {
            $this->logger->debug('DataProvider - No items found, creating default empty data structure');
            $this->loadedData[null] = [
                'hero_banners' => [],
                'five_step_routine_banner' => '',
                'three_step_routine_banner' => '',
                'discover_your_formula_banner' => '',
                'best_of_korean_formula_banner' => '',
                'discover_korean_ingredients_banners' => [],
                'perfect_gift_image' => '',
                'bottom_banner' => '',
                'active' => 0 // Default active to 0 for new records (matches db_schema.xml)
            ];
        }

        // Debug: Log final loaded data structure
        $this->logger->debug('DataProvider - Final loaded data structure: ' . json_encode($this->loadedData));
        
        return $this->loadedData;
    }
    
    /**
     * Check if file exists in media directory
     */
    private function fileExists($relativePath)
    {
        $mediaDirectory = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Filesystem::class)
            ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        
        return $mediaDirectory->isExist($relativePath);
    }
    
    /**
     * Get file size in bytes
     */
    private function getFileSize($relativePath)
    {
        $mediaDirectory = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Filesystem::class)
            ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        
        if ($mediaDirectory->isExist($relativePath)) {
            return $mediaDirectory->stat($relativePath)['size'] ?? 0;
        }
        
        return 0;
    }
}