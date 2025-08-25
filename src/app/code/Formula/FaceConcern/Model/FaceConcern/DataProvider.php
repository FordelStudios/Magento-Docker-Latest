<?php
namespace Formula\FaceConcern\Model\FaceConcern;

use Formula\FaceConcern\Model\ResourceModel\FaceConcern\CollectionFactory;
use Formula\FaceConcern\Model\FaceConcernFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $collection;
    protected $dataPersistor;
    protected $loadedData;
    protected $faceconcernFactory;
    protected $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        FaceConcernFactory $faceconcernFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->faceconcernFactory = $faceconcernFactory;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $faceconcern) {
            $data = $faceconcern->getData();
            
            if (isset($data['logo']) && $data['logo']) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $data['logo'] = [
                    [
                        'name' => $data['logo'],
                        'url' => $mediaUrl . 'faceconcern/logo/' . $data['logo'],
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
            
            $this->loadedData[$faceconcern->getId()] = $data;
        }

        $data = $this->dataPersistor->get('faceconcern');
        if (!empty($data)) {
            $faceconcern = $this->faceconcernFactory->create();
            $faceconcern->setData($data);
            $this->loadedData[$faceconcern->getId()] = $faceconcern->getData();
            $this->dataPersistor->clear('faceconcern');
        }

        return $this->loadedData ?: [];
    }
}