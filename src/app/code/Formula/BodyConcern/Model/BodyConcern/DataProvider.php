<?php
namespace Formula\BodyConcern\Model\BodyConcern;

use Formula\BodyConcern\Model\ResourceModel\BodyConcern\CollectionFactory;
use Formula\BodyConcern\Model\BodyConcernFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $collection;
    protected $dataPersistor;
    protected $loadedData;
    protected $bodyconcernFactory;
    protected $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        BodyConcernFactory $bodyconcernFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->bodyconcernFactory = $bodyconcernFactory;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $bodyconcern) {
            $data = $bodyconcern->getData();
            
            if (isset($data['logo']) && $data['logo']) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $data['logo'] = [
                    [
                        'name' => $data['logo'],
                        'url' => $mediaUrl . 'bodyconcern/logo/' . $data['logo'],
                        'type' => 'image'
                    ]
                ];
            }

            if (isset($data['tags']) && !empty($data['tags'])) {
                $decodedTags = json_decode($data['tags'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedTags)) {
                    $data['tags'] = implode(',', $decodedTags);
                }
            }
            
            $this->loadedData[$bodyconcern->getId()] = $data;
        }

        $data = $this->dataPersistor->get('bodyconcern');
        if (!empty($data)) {
            $bodyconcern = $this->bodyconcernFactory->create();
            $bodyconcern->setData($data);
            $this->loadedData[$bodyconcern->getId()] = $bodyconcern->getData();
            $this->dataPersistor->clear('bodyconcern');
        }

        return $this->loadedData ?: [];
    }
}