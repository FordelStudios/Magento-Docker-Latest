<?php
namespace Formula\SkinConcern\Model;

use Formula\SkinConcern\Api\SkinConcernRepositoryInterface;
use Formula\SkinConcern\Api\Data\SkinConcernInterface;
use Formula\SkinConcern\Model\ResourceModel\SkinConcern as ResourceSkinConcern;
use Formula\SkinConcern\Model\ResourceModel\SkinConcern\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class SkinConcernRepository implements SkinConcernRepositoryInterface
{
    protected $resource;
    protected $skinconcernFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;
    protected $request;
    protected $eavConfig;

    public function __construct(
        ResourceSkinConcern $resource,
        SkinConcernFactory $skinconcernFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        EavConfig $eavConfig,
        RequestInterface $request
    ) {
        $this->resource = $resource;
        $this->skinconcernFactory = $skinconcernFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->request = $request;
        $this->eavConfig = $eavConfig;
    }

    public function save(SkinConcernInterface $skinconcern)
    {
        try {
            $this->resource->save($skinconcern);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $skinconcern;
    }

    public function getById($skinconcernId)
    {
        $skinconcern = $this->skinconcernFactory->create();
        $this->resource->load($skinconcern, $skinconcernId);
        if (!$skinconcern->getId()) {
            throw new NoSuchEntityException(new \Magento\Framework\Phrase('SkinConcern with id %1 does not exist.', [$skinconcernId]));
        }
        return $skinconcern;
    }

    public function getList(SearchCriteriaInterface $searchCriteria){
        try {
            $collection = $this->collectionFactory->create();

            // Check for onlyIncludeWithProducts parameter
            $onlyIncludeWithProducts = $this->request->getParam('onlyIncludeWithProducts');
            
            if ($onlyIncludeWithProducts === 'true' || $onlyIncludeWithProducts === '1') {
                try {
                    // Get attribute ID for 'skinconcern'
                    $skinconcernAttribute = $this->eavConfig->getAttribute(
                        \Magento\Catalog\Model\Product::ENTITY,
                        'skinconcern'
                    );

                    $attributeId = $skinconcernAttribute->getAttributeId();

                    // Get EAV varchar table
                    $productEavTable = $collection->getTable('catalog_product_entity_varchar');

                    // Join using FIND_IN_SET for multiselect values
                    $collection->getSelect()->joinInner(
                        ['cev' => $productEavTable],
                        'FIND_IN_SET(main_table.skinconcern_id, cev.value) AND cev.attribute_id = ' . (int)$attributeId,
                        []
                    )->group('main_table.skinconcern_id');

                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Could not filter by product skinconcerns: %1', $e->getMessage())
                    );
                }
            }

            
            // Apply search criteria filters if any
            foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
                foreach ($filterGroup->getFilters() as $filter) {
                    $condition = $filter->getConditionType() ?: 'eq';
                    $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
                }
            }
            
            // Apply sorting
            $sortOrders = $searchCriteria->getSortOrders();
            if ($sortOrders) {
                foreach ($sortOrders as $sortOrder) {
                    $collection->addOrder(
                        $sortOrder->getField(),
                        ($sortOrder->getDirection() == \Magento\Framework\Api\SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                    );
                }
            }
            
            // Apply pagination
            $collection->setCurPage($searchCriteria->getCurrentPage());
            $collection->setPageSize($searchCriteria->getPageSize());
            
            // Get raw items from collection
            $items = $collection->getItems();
            
            // Convert items to array format
            $skinconcernItems = [];
            foreach ($items as $item) {
                $skinconcernItems[] = [
                    'skinconcern_id' => $item->getSkinConcernId(),
                    'name' => $item->getName(),
                    'description' => $item->getDescription(),
                    'logo' => $item->getLogo(),
                    'tags' => $item->getTags(),
                    'created_at' => $item->getCreatedAt(),
                    'updated_at' => $item->getUpdatedAt()
                ];
            }
            
            $searchResults = $this->searchResultsFactory->create();
            $searchResults->setSearchCriteria($searchCriteria);
            $searchResults->setItems($skinconcernItems);
            $searchResults->setTotalCount($collection->getSize());
            
            return $searchResults;
            
        } catch (\Exception $e) {
            // Add error logging here
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not retrieve skinconcerns: %1', $e->getMessage())
            );
        }
    }

    public function delete(SkinConcernInterface $skinconcern)
    {
        try {
            $this->resource->delete($skinconcern);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($skinconcernId)
    {
        return $this->delete($this->getById($skinconcernId));
    }

    public function update($skinconcernId, SkinConcernInterface $skinconcern)
    {
        try {
            // Load existing skinconcern
            $existingSkinConcern = $this->getById($skinconcernId);
            
            $existingSkinConcern->setName($skinconcern->getName());
            $existingSkinConcern->setDescription($skinconcern->getDescription());
            $existingSkinConcern->setLogo($skinconcern->getLogo());
            $existingSkinConcern->setTags($skinconcern->getTags());

            $this->resource->save($existingSkinConcern);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                 new \Magento\Framework\Phrase('Unable to update skinconcern: %1', [$exception->getMessage()])
            );
        }   

        return $existingSkinConcern;
    }

}