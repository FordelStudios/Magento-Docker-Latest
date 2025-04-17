<?php
namespace Formula\Blog\Model\Blog;

use Formula\Blog\Model\ResourceModel\Blog\CollectionFactory;
use Formula\Blog\Model\BlogFactory;
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
    protected $blogFactory;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        BlogFactory $blogFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
        $this->blogFactory = $blogFactory;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        
        $items = $this->collection->getItems();
        
        foreach ($items as $blog) {
            $data = $blog->getData();
            
            if (isset($data['image']) && $data['image']) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $data['image'] = [
                    [
                        'name' => $data['image'],
                        'url' => $mediaUrl . 'blog/image/' . $data['image'],
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
            
            // Format product_ids
            if (isset($data['product_ids']) && !is_array($data['product_ids']) && !empty($data['product_ids'])) {
                $data['product_ids'] = explode(',', $data['product_ids']);
            }
            
            $this->loadedData[$blog->getId()] = $data;
        }
        
        // Check if we have data in the persistor from a failed save attempt
        $data = $this->dataPersistor->get('blog');
        if (!empty($data)) {
            $blog = $this->blogFactory->create();
            $blog->setData($data);
            $this->loadedData[$blog->getId()] = $blog->getData();
            $this->dataPersistor->clear('blog');
        }
        
        return $this->loadedData;
    }
}