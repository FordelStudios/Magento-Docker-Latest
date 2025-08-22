<?php
namespace Formula\Blog\Ui\DataProvider\Blog;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Formula\Blog\Model\ResourceModel\Blog\CollectionFactory;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class ListingDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    protected $collection;
    protected $storeManager;
    protected $categoryCollectionFactory;

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
        CategoryCollectionFactory $categoryCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
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
            
            // Add image URLs for image
            if (!empty($itemData['image'])) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $itemData['image_src'] = $mediaUrl . 'blog/image/' . $itemData['image'];
                $itemData['image_alt'] = $itemData['name'] ?? 'Blog Image';
                $itemData['image_link'] = $mediaUrl . 'blog/image/' . $itemData['image'];
                $itemData['image_orig_src'] = $mediaUrl . 'blog/image/' . $itemData['image'];
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
            
            // Add image URLs for image
            if (!empty($itemData['image'])) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $itemData['image_src'] = $mediaUrl . 'blog/image/' . $itemData['image'];
                $itemData['image_alt'] = $itemData['name'] ?? 'Blog Image';
                $itemData['image_link'] = $mediaUrl . 'blog/image/' . $itemData['image'];
                $itemData['image_orig_src'] = $mediaUrl . 'blog/image/' . $itemData['image'];
            }

            if (isset($itemData['tags']) && !empty($itemData['tags'])) {
                $decodedTags = json_decode($itemData['tags'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedTags)) {
                    $itemData['tags'] = implode(',', $decodedTags);
                }
            }
            
            // Handle category_ids display
            if (isset($itemData['category_ids']) && !empty($itemData['category_ids'])) {
                $decodedCategoryIds = json_decode($itemData['category_ids'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedCategoryIds)) {
                    $categoryNames = $this->getCategoryNames($decodedCategoryIds);
                    $itemData['category_ids'] = implode(', ', $categoryNames);
                } else {
                    $itemData['category_ids'] = '';
                }
            } else {
                $itemData['category_ids'] = '';
            }
            
            $data['items'][] = $itemData;
        }

        return $data;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Get category names by IDs
     *
     * @param array $categoryIds
     * @return array
     */
    private function getCategoryNames(array $categoryIds)
    {
        if (empty($categoryIds)) {
            return [];
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addFieldToFilter('entity_id', ['in' => $categoryIds]);

        $categoryNames = [];
        foreach ($collection as $category) {
            $categoryNames[] = $category->getName();
        }

        return $categoryNames;
    }
}