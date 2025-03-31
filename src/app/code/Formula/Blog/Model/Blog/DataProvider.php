<?php
namespace Formula\Blog\Model\Blog;

use Formula\Blog\Model\ResourceModel\Blog\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $collection;
    protected $dataPersistor;
    protected $loadedData;
    protected $storeManager;
    protected $filesystem;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
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
            
            // Format image data
            if (isset($data['image']) && $data['image']) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                $imagePath = $mediaUrl . 'formula_blog/image/' . $data['image'];
                $data['image'] = [
                    [
                        'name' => $data['image'],
                        'url' => $imagePath
                    ]
                ];
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
            $blogId = isset($data['blog_id']) ? $data['blog_id'] : null;
            $this->loadedData[$blogId] = $data;
            $this->dataPersistor->clear('blog');
        }
        
        return $this->loadedData;
    }
}