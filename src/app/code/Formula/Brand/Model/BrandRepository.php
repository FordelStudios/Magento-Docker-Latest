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
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;

class BrandRepository implements BrandRepositoryInterface
{
    protected $resource;
    protected $brandFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;
    protected $request;
    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    protected $filterGroupBuilder;

    public function __construct(
        ResourceBrand $resource,
        BrandFactory $brandFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        RequestInterface $request,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->resource = $resource;
        $this->brandFactory = $brandFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->request = $request;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
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

            // Manual SearchCriteria building from request parameters if needed
            $searchCriteriaData = $this->request->getParam('searchCriteria', []);

            // Build searchCriteria from request params if they exist
            if (!empty($searchCriteriaData)) {
                $searchCriteria = $this->buildSearchCriteriaFromRequest($searchCriteriaData);
            }

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
                    'is_trending' => (bool)$item->getIsTrending(),
                    'is_popular' => (bool)$item->getIsPopular(),
                    'country' => $item->getCountry(),
                    'certifications' => $item->getCertifications(),
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

            if (isset($data['is_trending'])) {
                $existingBrand->setIsTrending($brand->getIsTrending());
            }

            if (isset($data['is_popular'])) {
                $existingBrand->setIsPopular($brand->getIsPopular());
            }

            if (isset($data['country'])) {
                $existingBrand->setCountry($brand->getCountry());
            }

            if (isset($data['certifications'])) {
                $existingBrand->setCertifications($brand->getCertifications());
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

    /**
     * Build SearchCriteria from request parameters
     *
     * @param array $searchCriteriaData
     * @return SearchCriteriaInterface
     */
    private function buildSearchCriteriaFromRequest($searchCriteriaData)
    {
        $filterGroups = [];

        // Support both filter_groups (snake_case) and filterGroups (camelCase)
        $filterGroupsData = $searchCriteriaData['filter_groups'] ?? $searchCriteriaData['filterGroups'] ?? null;

        if ($filterGroupsData) {
            foreach ($filterGroupsData as $filterGroupData) {
                if (isset($filterGroupData['filters'])) {
                    $filters = [];
                    foreach ($filterGroupData['filters'] as $filterData) {
                        if (isset($filterData['field']) && isset($filterData['value'])) {
                            // Support both condition_type (snake_case) and conditionType (camelCase)
                            $conditionType = $filterData['condition_type'] ?? $filterData['conditionType'] ?? 'eq';

                            $filter = $this->filterBuilder
                                ->setField($filterData['field'])
                                ->setValue($filterData['value'])
                                ->setConditionType($conditionType)
                                ->create();
                            $filters[] = $filter;
                        }
                    }
                    if (!empty($filters)) {
                        $filterGroup = $this->filterGroupBuilder->setFilters($filters)->create();
                        $filterGroups[] = $filterGroup;
                    }
                }
            }
        }

        // Build the search criteria with filter groups
        $this->searchCriteriaBuilder->setFilterGroups($filterGroups);

        // Add sort orders if present - support both sort_orders (snake_case) and sortOrders (camelCase)
        $sortOrdersData = $searchCriteriaData['sort_orders'] ?? $searchCriteriaData['sortOrders'] ?? null;

        if ($sortOrdersData) {
            foreach ($sortOrdersData as $sortOrderData) {
                if (isset($sortOrderData['field'])) {
                    $this->searchCriteriaBuilder->addSortOrder(
                        $sortOrderData['field'],
                        $sortOrderData['direction'] ?? SortOrder::SORT_ASC
                    );
                }
            }
        }

        // Add page size if present - support both page_size (snake_case) and pageSize (camelCase)
        $pageSize = $searchCriteriaData['page_size'] ?? $searchCriteriaData['pageSize'] ?? null;
        if ($pageSize) {
            $this->searchCriteriaBuilder->setPageSize($pageSize);
        }

        // Add current page if present - support both current_page (snake_case) and currentPage (camelCase)
        $currentPage = $searchCriteriaData['current_page'] ?? $searchCriteriaData['currentPage'] ?? null;
        if ($currentPage) {
            $this->searchCriteriaBuilder->setCurrentPage($currentPage);
        }

        return $this->searchCriteriaBuilder->create();
    }

}