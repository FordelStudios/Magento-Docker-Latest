<?php
namespace Formula\Brand\Ui\DataProvider\Brand;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Formula\Brand\Model\ResourceModel\Brand\CollectionFactory;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Api\Filter;

class ListingDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    protected $collection;
    protected $storeManager;
    protected $request;

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
        $this->request = $request;
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

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
        // Handle fulltext search
        if ($filter->getField() === 'fulltext') {
            $value = $filter->getValue();
            $this->getCollection()->addFieldToFilter(
                [
                    'main_table.name',
                    'main_table.description',
                    'main_table.tagline',
                    'main_table.tags'
                ],
                [
                    ['like' => "%$value%"],
                    ['like' => "%$value%"],
                    ['like' => "%$value%"],
                    ['like' => "%$value%"]
                ]
            );
            return;
        }
        
        // Handle boolean filters specially
        if (in_array($filter->getField(), ['is_korean', 'is_homegrown', 'is_trending', 'is_popular'])) {
            $value = $filter->getValue();
            
            // Convert string 'Yes'/'No' to boolean 1/0
            if ($value === 'Yes') {
                $value = 1;
            } elseif ($value === 'No') {
                $value = 0;
            }
            
            $this->getCollection()->addFieldToFilter($filter->getField(), (int)$value);
            return;
        }

        // Handle specific field text searches with proper LIKE filters
        if (in_array($filter->getField(), ['name', 'description', 'tagline', 'tags'])) {
            $field = $filter->getField();
            $value = $filter->getValue();
            
            // Check if this is a field-specific keyword search (e.g., "Description: updated")
            if (strpos($field, 'keyword_') === 0 || 
                preg_match('/^(name|description|tagline|tags)$/', $field)) {
                // Extract the actual field name if it has a keyword_ prefix
                if (strpos($field, 'keyword_') === 0) {
                    $field = substr($field, 8); // Remove 'keyword_' prefix
                }
                
                // Apply LIKE filter
                $this->getCollection()->addFieldToFilter(
                    "main_table.$field",
                    ['like' => "%$value%"]
                );
                return;
            }
        }
        
        // Default behavior for all other filters
        parent::addFilter($filter);
    }

     /**
     * Returns Search result
     *
     * @return SearchResultInterface
     */
    public function getSearchResult()
    {
        return $this->searchResultToOutput(
            parent::getSearchResult()
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
                $itemData['logo_src'] = $mediaUrl . 'brand/logo/' . $itemData['logo'];
                $itemData['logo_alt'] = $itemData['name'] ?? 'Brand Logo';
                $itemData['logo_link'] = $mediaUrl . 'brand/logo/' . $itemData['logo'];
                $itemData['logo_orig_src'] = $mediaUrl . 'brand/logo/' . $itemData['logo'];
            }
            

            
            $arrItems['items'][] = $itemData;
        }

        $arrItems['totalRecords'] = $searchResult->getTotalCount();

        return $arrItems;
    }

    public function getData()
    {

        $searchCriteria = $this->getSearchCriteria();
        $pageSize = $searchCriteria->getPageSize();
        $currentPage = $searchCriteria->getCurrentPage();


        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->setPageSize($pageSize);
            $this->getCollection()->setCurPage($currentPage);
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
                $itemData['logo_src'] = $mediaUrl . 'brand/logo/' . $itemData['logo'];
                $itemData['logo_alt'] = $itemData['name'] ?? 'Brand Logo';
                $itemData['logo_link'] = $mediaUrl . 'brand/logo/' . $itemData['logo'];
                $itemData['logo_orig_src'] = $mediaUrl . 'brand/logo/' . $itemData['logo'];
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