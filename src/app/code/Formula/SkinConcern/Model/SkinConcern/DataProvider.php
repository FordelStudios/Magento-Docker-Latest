<?php
namespace Formula\SkinConcern\Model\SkinConcern;

use Formula\SkinConcern\Model\ResourceModel\SkinConcern\CollectionFactory;
use Formula\SkinConcern\Model\SkinConcernFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $collection;
    protected $dataPersistor;
    protected $loadedData;
    protected $skinconcernFactory;
    protected $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        SkinConcernFactory $skinconcernFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->skinconcernFactory = $skinconcernFactory;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $skinconcern) {
            $data = $skinconcern->getData();
            
            if (isset($data['logo']) && $data['logo']) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $data['logo'] = [
                    [
                        'name' => $data['logo'],
                        'url' => $mediaUrl . 'skinconcern/logo/' . $data['logo'],
                        'type' => 'image'
                    ]
                ];
            }
            
            $this->loadedData[$skinconcern->getId()] = $data;
        }

        $data = $this->dataPersistor->get('skinconcern');
        if (!empty($data)) {
            $skinconcern = $this->skinconcernFactory->create();
            $skinconcern->setData($data);
            $this->loadedData[$skinconcern->getId()] = $skinconcern->getData();
            $this->dataPersistor->clear('skinconcern');
        }

        return $this->loadedData ?: [];
    }
}