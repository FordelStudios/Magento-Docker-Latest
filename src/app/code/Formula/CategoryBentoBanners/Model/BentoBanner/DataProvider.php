<?php
namespace Formula\CategoryBentoBanners\Model\BentoBanner;

use Formula\CategoryBentoBanners\Model\ResourceModel\CategoryBentoBanner\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Store\Model\StoreManagerInterface;

class DataProvider extends AbstractDataProvider
{
    protected $collection;
    protected $dataPersistor;
    protected $loadedData;
    protected $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $model) {
            $data = $model->getData();
            
            if (isset($data['banner_image']) && $data['banner_image']) {
                $imageName = $data['banner_image'];
                unset($data['banner_image']);
                $data['banner_image'][0]['name'] = $imageName;
                $data['banner_image'][0]['url'] = $this->getMediaUrl() . $imageName;
            }
            
            $this->loadedData[$model->getId()] = $data;
        }

        $data = $this->dataPersistor->get('categorybentobanner_form_data');
        if (!empty($data)) {
            $model = $this->collection->getNewEmptyItem();
            $model->setData($data);
            $this->loadedData[$model->getId()] = $model->getData();
            $this->dataPersistor->clear('categorybentobanner_form_data');
        }

        return $this->loadedData;
    }

    private function getMediaUrl()
    {
        $mediaUrl = $this->storeManager->getStore()
                         ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        return $mediaUrl . 'formula/categorybentobanner/';
    }
}