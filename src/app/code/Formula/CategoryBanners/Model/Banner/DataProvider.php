<?php
// app/code/Formula/CategoryBanners/Model/Banner/DataProvider.php
namespace Formula\CategoryBanners\Model\Banner;

use Formula\CategoryBanners\Model\ResourceModel\CategoryBanner\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Formula\CategoryBanners\Model\ResourceModel\CategoryBanner\Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $bannerCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $bannerCollectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $bannerCollectionFactory->create();
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
        
        foreach ($items as $banner) {
            $this->loadedData[$banner->getId()] = $banner->getData();

            // Process subcategories
            if (isset($this->loadedData[$banner->getId()]['subcategories'])) {
                $subcategories = $this->loadedData[$banner->getId()]['subcategories'];
                if (!empty($subcategories) && is_string($subcategories)) {
                    $this->loadedData[$banner->getId()]['subcategories'] = explode(',', $subcategories);
                }
            }
            
            if ($banner->getBannerImage()) {
                $bannerImage = $banner->getBannerImage();
                if (is_string($bannerImage)) {
                    $imageUrl = $this->storeManager->getStore()->getBaseUrl(
                        UrlInterface::URL_TYPE_MEDIA
                    ) . 'formula/categorybanner/' . $bannerImage;
                    
                    $this->loadedData[$banner->getId()]['banner_image'] = [
                        [
                            'name' => $bannerImage,
                            'url' => $imageUrl,
                            'size' => $this->getImageSize($bannerImage),
                            'type' => $this->getMimeType($bannerImage)
                        ]
                    ];
                }
            }
        }

        $data = $this->dataPersistor->get('category_banner');
        if (!empty($data)) {
            $bannerId = isset($data['entity_id']) ? $data['entity_id'] : null;
            $this->loadedData[$bannerId] = $data;
            $this->dataPersistor->clear('category_banner');
        }

        return $this->loadedData;
    }

    /**
     * Get MIME type of file
     *
     * @param string $file
     * @return string
     */
    private function getMimeType($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
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

    /**
     * Get image size
     *
     * @param string $file
     * @return int
     */
    private function getImageSize($file)
    {
        $filePath = $this->getFilePath($file);
        return file_exists($filePath) ? filesize($filePath) : 0;
    }

    /**
     * Get file path
     *
     * @param string $file
     * @return string
     */
    private function getFilePath($file)
    {
        return BP . '/pub/media/formula/categorybanner/' . $file;
    }
}