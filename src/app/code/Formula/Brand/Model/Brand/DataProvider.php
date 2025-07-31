<?php
namespace Formula\Brand\Model\Brand;

use Formula\Brand\Model\ResourceModel\Brand\CollectionFactory;
use Formula\Brand\Model\BrandFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $collection;
    protected $dataPersistor;
    protected $loadedData;
    protected $brandFactory;
    protected $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        BrandFactory $brandFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->brandFactory = $brandFactory;
        $this->storeManager = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $brand) {
            $data = $brand->getData();
            
            if (isset($data['logo']) && $data['logo']) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $data['logo'] = [
                    [
                        'name' => $data['logo'],
                        'url' => $mediaUrl . 'brand/logo/' . $data['logo'],
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

            if (isset($data['promotional_banners']) && $data['promotional_banners']) {
                try {
                    $banners = json_decode($data['promotional_banners'], true);
                    if (is_array($banners)) {
                        $formattedBanners = [];
                        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                        
                        foreach ($banners as $banner) {
                            $formattedBanners[] = [
                                'name' => $banner,
                                'url' => $mediaUrl . 'brand/banner/' . $banner,
                                'type' => 'image'
                            ];
                        }
                        
                        $data['promotional_banners'] = $formattedBanners;
                    }
                } catch (\Exception $e) {
                    // Handle error
                }
            }
            
            $this->loadedData[$brand->getId()] = $data;
        }

        $data = $this->dataPersistor->get('brand');
        if (!empty($data)) {
            $brand = $this->brandFactory->create();
            $brand->setData($data);
            $this->loadedData[$brand->getId()] = $brand->getData();
            $this->dataPersistor->clear('brand');
        }

        return $this->loadedData ?: [];
    }
}