<?php
namespace Formula\SkinType\Model\SkinType;

use Formula\SkinType\Model\ResourceModel\SkinType\CollectionFactory;
use Formula\SkinType\Model\SkinTypeFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $collection;
    protected $dataPersistor;
    protected $loadedData;
    protected $skintypeFactory;
    protected $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        SkinTypeFactory $skintypeFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->skintypeFactory = $skintypeFactory;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $skintype) {
            $data = $skintype->getData();
            
            if (isset($data['logo']) && $data['logo']) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $data['logo'] = [
                    [
                        'name' => $data['logo'],
                        'url' => $mediaUrl . 'skintype/logo/' . $data['logo'],
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
            
            $this->loadedData[$skintype->getId()] = $data;
        }

        $data = $this->dataPersistor->get('skintype');
        if (!empty($data)) {
            $skintype = $this->skintypeFactory->create();
            $skintype->setData($data);
            $this->loadedData[$skintype->getId()] = $skintype->getData();
            $this->dataPersistor->clear('skintype');
        }

        return $this->loadedData ?: [];
    }
}