<?php
namespace Formula\Reel\Model\Reel;

use Formula\Reel\Model\ResourceModel\Reel\CollectionFactory;
use Formula\Reel\Model\ReelFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $collection;
    protected $dataPersistor;
    protected $loadedData;
    protected $storeManager;
    protected $filesystem;
    protected $reelFactory;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        ReelFactory $reelFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
        $this->reelFactory = $reelFactory;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        
        $items = $this->collection->getItems();
        
        foreach ($items as $reel) {
            $data = $reel->getData();
            
            if (isset($data['video']) && $data['video']) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $data['video'] = [
                    [
                        'name' => $data['video'],
                        'url' => $mediaUrl . 'reel/video/' . $data['video'],
                        'type' => 'video'
                    ]
                ];
            }
            
            // Format product_ids
            if (isset($data['product_ids']) && !is_array($data['product_ids']) && !empty($data['product_ids'])) {
                $data['product_ids'] = explode(',', $data['product_ids']);
            }
            
            $this->loadedData[$reel->getId()] = $data;
        }
        
        // Check if we have data in the persistor from a failed save attempt
        $data = $this->dataPersistor->get('reel');
        if (!empty($data)) {
            $reel = $this->reelFactory->create();
            $reel->setData($data);
            $this->loadedData[$reel->getId()] = $reel->getData();
            $this->dataPersistor->clear('reel');
        }
        
        return $this->loadedData;
    }
}