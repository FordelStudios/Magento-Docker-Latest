<?php
namespace Formula\CategoryBentoBanners\Model\BentoBanner;

use Formula\CategoryBentoBanners\Model\ResourceModel\CategoryBentoBanner\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class DataProvider extends AbstractDataProvider
{
    protected $collection;
    protected $collectionFactory;
    protected $dataPersistor;
    protected $loadedData;
    protected $storeManager;
    protected $request;
    protected $logger;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        array $meta = [],
        array $data = [],
        LoggerInterface $logger = null
    ) {
        $this->collection = $collectionFactory->create();
        $this->collectionFactory = $collectionFactory;
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->logger = $logger ?: \Magento\Framework\App\ObjectManager::getInstance()->get(LoggerInterface::class);
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $this->logger->info('DataProvider getData: Called');
        
        if (isset($this->loadedData)) {
            $this->logger->info('DataProvider getData: Returning cached data', ['cached_data' => $this->loadedData]);
            return $this->loadedData;
        }

        $this->loadedData = [];

        // Get the ID from request (for edit mode)
        $id = $this->request->getParam('entity_id');
        $this->logger->info('DataProvider getData: Request params', [
            'entity_id' => $id,
            'all_params' => $this->request->getParams()
        ]);

        if ($id) {
            // Create a fresh collection to avoid any existing filters
            $this->collection = $this->collectionFactory->create();
            $this->collection->addFieldToFilter('entity_id', $id);
            $this->logger->info('DataProvider getData: Added filter for entity_id', ['id' => $id]);
        }

        $this->logger->info('DataProvider getData: Collection SQL', ['sql' => $this->collection->getSelect()->__toString()]);

        $items = $this->collection->getItems();
        $this->logger->info('DataProvider getData: Collection items count', ['count' => count($items)]);

        foreach ($items as $model) {
            $data = $model->getData();
            $this->logger->info('DataProvider getData: Processing model', ['model_data' => $data]);
            
            if (isset($data['banner_image']) && $data['banner_image']) {
                $imageName = $data['banner_image'];
                $imageUrl = $this->getMediaUrl() . $imageName;
                unset($data['banner_image']);
                $data['banner_image'][0] = [
                    'name' => $imageName,
                    'url' => $imageUrl,
                    'size' => $this->getImageSize($imageName),
                    'type' => $this->getImageMimeType($imageName),
                    'file' => $imageName
                ];
                $this->logger->info('DataProvider getData: Processed image', ['image_data' => $data['banner_image']]);
            }
            
            $this->loadedData[$model->getId()] = $data;
            $this->logger->info('DataProvider getData: Added to loadedData', ['model_id' => $model->getId()]);
        }

        // Handle persisted data (for form errors)
        $persistedData = $this->dataPersistor->get('categorybentobanner_form_data');
        $this->logger->info('DataProvider getData: Persisted data', ['persisted_data' => $persistedData]);
        if (!empty($persistedData)) {
            $persistedId = isset($persistedData['entity_id']) ? $persistedData['entity_id'] : null;
            $this->loadedData[$persistedId] = $persistedData;
            $this->dataPersistor->clear('categorybentobanner_form_data');
            $this->logger->info('DataProvider getData: Added persisted data', ['persisted_id' => $persistedId]);
        }

        $this->logger->info('DataProvider getData: Final loaded data', ['loaded_data' => $this->loadedData]);
        return $this->loadedData;
    }

    private function getMediaUrl()
    {
        $mediaUrl = $this->storeManager->getStore()
                         ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl . 'formula/categorybentobanner/';
    }

    private function getImageSize($imageName)
    {
        try {
            $mediaPath = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $fullPath = str_replace($mediaPath, '', $this->getMediaUrl()) . $imageName;
            $directoryRead = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Filesystem::class)
                ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
            
            if ($directoryRead->isExist('formula/categorybentobanner/' . $imageName)) {
                $stat = $directoryRead->stat('formula/categorybentobanner/' . $imageName);
                return isset($stat['size']) ? $stat['size'] : 0;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error getting image size: ' . $e->getMessage());
        }
        return 0;
    }

    private function getImageMimeType($imageName)
    {
        try {
            $directoryRead = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Filesystem::class)
                ->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
            
            if ($directoryRead->isExist('formula/categorybentobanner/' . $imageName)) {
                $extension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
                switch ($extension) {
                    case 'jpg':
                    case 'jpeg':
                        return 'image/jpeg';
                    case 'png':
                        return 'image/png';
                    case 'gif':
                        return 'image/gif';
                    default:
                        return 'application/octet-stream';
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error getting image mime type: ' . $e->getMessage());
        }
        return 'application/octet-stream';
    }
}