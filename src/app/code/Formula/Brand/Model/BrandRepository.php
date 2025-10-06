<?php
namespace Formula\Brand\Model;

use Formula\Brand\Api\BrandRepositoryInterface;
use Formula\Brand\Api\Data\BrandInterface;
use Formula\Brand\Model\ResourceModel\Brand as ResourceBrand;
use Formula\Brand\Model\ResourceModel\Brand\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SortOrder;

class BrandRepository implements BrandRepositoryInterface
{
    protected $resource;
    protected $brandFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;

    public function __construct(
        ResourceBrand $resource,
        BrandFactory $brandFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->brandFactory = $brandFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function save(BrandInterface $brand)
    {
        try {
            $brand->setPromotionalBanners(json_encode($brand->getPromotionalBanners()));

            $this->resource->save($brand);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $brand;
    }

    public function getById($brandId)
    {
        $brand = $this->brandFactory->create();
        $this->resource->load($brand, $brandId);
        if (!$brand->getId()) {
            throw new NoSuchEntityException(new \Magento\Framework\Phrase('Brand with id %1 does not exist.', [$brandId]));
        }
        $brand->setData('created_at', $brand->getCreatedAt());
        $brand->setData('updated_at', $brand->getUpdatedAt());

        return $brand;
    }

   public function getList(SearchCriteriaInterface $searchCriteria)
    {
        try {
            $collection = $this->collectionFactory->create();
            
            // Apply filters from search criteria
            foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
                $fields = [];
                $conditions = [];
                
                foreach ($filterGroup->getFilters() as $filter) {
                    $fields[] = $filter->getField();
                    $condition = $filter->getConditionType() ?: 'eq';
                    $conditions[] = [$condition => $filter->getValue()];
                }
                
                if ($fields) {
                    $collection->addFieldToFilter($fields, $conditions);
                }
            }
            
            // Apply sort orders
            $sortOrders = $searchCriteria->getSortOrders();
            if ($sortOrders) {
                foreach ($sortOrders as $sortOrder) {
                    $field = $sortOrder->getField();
                    $direction = $sortOrder->getDirection() == SortOrder::SORT_ASC ? 'ASC' : 'DESC';
                    $collection->addOrder($field, $direction);
                }
            }
            
            // Apply pagination
            $pageSize = $searchCriteria->getPageSize();
            if ($pageSize) {
                $collection->setPageSize($pageSize);
            }
            
            $currentPage = $searchCriteria->getCurrentPage();
            if ($currentPage) {
                $collection->setCurPage($currentPage);
            }
            
            // Get items from filtered, sorted, and paginated collection
            $items = $collection->getItems();
            
            // Convert items to array format
            $brandItems = [];
            foreach ($items as $item) {
                $brandItems[] = [
                    'brand_id' => $item->getBrandId(),
                    'name' => $item->getName(),
                    'description' => $item->getDescription(),
                    'tagline' => $item->getTagline(),
                    'logo' => $item->getLogo(),
                    'sale_page_banner' => $item->getSalePageBanner(),
                    'tags' => $item->getTags(),
                    'promotional_banners' => $item->getPromotionalBanners(),
                    'is_korean' => (bool)$item->getIsKorean(),
                    'is_global' => (bool)$item->getIsGlobal(),
                    'is_japanese' => (bool)$item->getIsJapanese(),
                    'is_african' => (bool)$item->getIsAfrican(),
                    'is_indian' => (bool)$item->getIsIndian(),
                    'is_homegrown' => (bool)$item->getIsHomegrown(),
                    'is_trending' => (bool)$item->getIsTrending(),
                    'is_popular' => (bool)$item->getIsPopular(),
                    'country' => $item->getCountry(),
                    'created_at' => $item->getCreatedAt(),
                    'updated_at' => $item->getUpdatedAt()
                ];
            }
            
            // Create and configure search results
            $searchResults = $this->searchResultsFactory->create();
            $searchResults->setSearchCriteria($searchCriteria);
            $searchResults->getSearchCriteria()->setFilterGroups($searchCriteria->getFilterGroups());
            $searchResults->setItems($brandItems);
            $searchResults->setTotalCount($collection->getSize());
            
            return $searchResults;
            
        } catch (\Exception $e) {
            // Add error logging here
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not retrieve brands: %1', $e->getMessage())
            );
        }
    }


    

    public function delete(BrandInterface $brand)
    {
        try {
            $this->resource->delete($brand);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($brandId)
    {
        return $this->delete($this->getById($brandId));
    }

    public function update($brandId, BrandInterface $brand)
    {
        try {
            // Load existing brand
            $existingBrand = $this->getById($brandId);
            
            // Get all data from the request
            $data = $brand->getData();
            
            // Only update fields that are explicitly set in the request
            if (isset($data['name'])) {
                $existingBrand->setName($brand->getName());
            }
            
            if (isset($data['description'])) {
                $existingBrand->setDescription($brand->getDescription());
            }
            
            if (isset($data['tagline'])) {
                $existingBrand->setTagline($brand->getTagline());
            }
            
            if (isset($data['logo'])) {
                $existingBrand->setLogo($brand->getLogo());
            }
            
            if (isset($data['sale_page_banner'])) {
                $existingBrand->setSalePageBanner($brand->getSalePageBanner());
            }
            
            // For JSON array fields, only update if they're explicitly in the request
            if (array_key_exists('promotional_banners', $data)) {
                $existingBrand->setPromotionalBanners($brand->getPromotionalBanners());
            }
            
            if (array_key_exists('tags', $data)) {
                $existingBrand->setTags($brand->getTags());
            }
            
            // For boolean fields
            if (isset($data['is_korean'])) {
                $existingBrand->setIsKorean($brand->getIsKorean());
            }

            if (isset($data['is_global'])) {
                $existingBrand->setIsGlobal($brand->getIsGlobal());
            }

            if (isset($data['is_japanese'])) {
                $existingBrand->setIsJapanese($brand->getIsJapanese());
            }

            if (isset($data['is_african'])) {
                $existingBrand->setIsAfrican($brand->getIsAfrican());
            }

            if (isset($data['is_indian'])) {
                $existingBrand->setIsIndian($brand->getIsIndian());
            }

            if (isset($data['is_homegrown'])) {
                $existingBrand->setIsHomegrown($brand->getIsHomegrown());
            }

            if (isset($data['is_trending'])) {
                $existingBrand->setIsTrending($brand->getIsTrending());
            }

            if (isset($data['is_popular'])) {
                $existingBrand->setIsPopular($brand->getIsPopular());
            }

            if (isset($data['country'])) {
                $existingBrand->setCountry($brand->getCountry());
            }

            // Save the updated brand
            $this->resource->save($existingBrand);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                new \Magento\Framework\Phrase('Unable to update brand: %1', [$exception->getMessage()])
            );
        }   

        return $existingBrand;
    }

}