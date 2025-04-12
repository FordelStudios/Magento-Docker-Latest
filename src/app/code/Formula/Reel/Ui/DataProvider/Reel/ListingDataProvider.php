<?php
namespace Formula\Reel\Ui\DataProvider\Reel;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Formula\Reel\Model\ResourceModel\Reel\CollectionFactory;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class ListingDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    protected $collection;
    protected $storeManager;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->storeManager = $storeManager;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

    protected function searchResultToOutput(SearchResultInterface $searchResult)
    {
        $arrItems = [];
        $arrItems['items'] = [];
        
        foreach ($searchResult->getItems() as $item) {
            $itemData = $item->getData();
            
            // Add video URLs for video
            if (!empty($itemData['video'])) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $itemData['video_src'] = $mediaUrl . 'reel/video/' . $itemData['video'];
                $itemData['video_alt'] = $itemData['name'] ?? 'Reel Video';
                $itemData['video_link'] = $mediaUrl . 'reel/video/' . $itemData['video'];
                $itemData['video_orig_src'] = $mediaUrl . 'reel/video/' . $itemData['video'];
            }
            
            $arrItems['items'][] = $itemData;
        }

        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        return $arrItems;
    }

    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        
        $items = $this->getCollection();
        
        $data = [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => []
        ];

        foreach ($items as $item) {
            $itemData = $item->getData();
            
            // Add video URLs for video
            if (!empty($itemData['video'])) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $itemData['video_src'] = $mediaUrl . 'reel/video/' . $itemData['video'];
                $itemData['video_alt'] = $itemData['name'] ?? 'Reel Video';
                $itemData['video_link'] = $mediaUrl . 'reel/video/' . $itemData['video'];
                $itemData['video_orig_src'] = $mediaUrl . 'reel/video/' . $itemData['video'];
            }
            
            $data['items'][] = $itemData;
        }

        return $data;
    }

    public function getCollection()
    {
        return $this->collection;
    }
}