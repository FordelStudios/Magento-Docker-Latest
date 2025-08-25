<?php
namespace Formula\FaceConcern\Ui\DataProvider\FaceConcern;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Formula\FaceConcern\Model\ResourceModel\FaceConcern\CollectionFactory;
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
            
            // Add image URLs for logo
            if (!empty($itemData['logo'])) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $itemData['logo_src'] = $mediaUrl . 'faceconcern/logo/' . $itemData['logo'];
                $itemData['logo_alt'] = $itemData['name'] ?? 'FaceConcern Logo';
                $itemData['logo_link'] = $mediaUrl . 'faceconcern/logo/' . $itemData['logo'];
                $itemData['logo_orig_src'] = $mediaUrl . 'faceconcern/logo/' . $itemData['logo'];
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
            
            // Add image URLs for logo
            if (!empty($itemData['logo'])) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $itemData['logo_src'] = $mediaUrl . 'faceconcern/logo/' . $itemData['logo'];
                $itemData['logo_alt'] = $itemData['name'] ?? 'FaceConcern Logo';
                $itemData['logo_link'] = $mediaUrl . 'faceconcern/logo/' . $itemData['logo'];
                $itemData['logo_orig_src'] = $mediaUrl . 'faceconcern/logo/' . $itemData['logo'];
            }
            
            if (isset($itemData['tags']) && !empty($itemData['tags'])) {
                $decodedTags = json_decode($itemData['tags'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedTags)) {
                    $itemData['tags'] = implode(',', $decodedTags);
                }
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