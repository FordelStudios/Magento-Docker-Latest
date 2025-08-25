<?php
namespace Formula\BodyConcern\Model;

use Formula\BodyConcern\Api\BodyConcernRepositoryInterface;
use Formula\BodyConcern\Api\Data\BodyConcernInterface;
use Formula\BodyConcern\Model\ResourceModel\BodyConcern as ResourceBodyConcern;
use Formula\BodyConcern\Model\ResourceModel\BodyConcern\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class BodyConcernRepository implements BodyConcernRepositoryInterface
{
    protected $resource;
    protected $bodyconcernFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;
    protected $request;
    protected $eavConfig;

    public function __construct(
        ResourceBodyConcern $resource,
        BodyConcernFactory $bodyconcernFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        EavConfig $eavConfig,
        RequestInterface $request
    ) {
        $this->resource = $resource;
        $this->bodyconcernFactory = $bodyconcernFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->request = $request;
        $this->eavConfig = $eavConfig;
    }

    public function save(BodyConcernInterface $bodyconcern)
    {
        try {
            $this->resource->save($bodyconcern);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $bodyconcern;
    }

    public function getById($bodyconcernId)
    {
        $bodyconcern = $this->bodyconcernFactory->create();
        $this->resource->load($bodyconcern, $bodyconcernId);
        if (!$bodyconcern->getId()) {
            throw new NoSuchEntityException(new \Magento\Framework\Phrase('BodyConcern with id %1 does not exist.', [$bodyconcernId]));
        }
        return $bodyconcern;
    }

    public function getList(SearchCriteriaInterface $searchCriteria){
        try {
            $collection = $this->collectionFactory->create();

            // Check for onlyIncludeWithProducts parameter
            $onlyIncludeWithProducts = $this->request->getParam('onlyIncludeWithProducts');
            
            if ($onlyIncludeWithProducts === 'true' || $onlyIncludeWithProducts === '1') {
                try {
                    // Get attribute ID for 'bodyconcern'
                    $bodyconcernAttribute = $this->eavConfig->getAttribute(
                        \Magento\Catalog\Model\Product::ENTITY,
                        'bodyconcern'
                    );

                    $attributeId = $bodyconcernAttribute->getAttributeId();

                    // Get EAV varchar table
                    $productEavTable = $collection->getTable('catalog_product_entity_varchar');

                    // Join using FIND_IN_SET for multiselect values
                    $collection->getSelect()->joinInner(
                        ['cev' => $productEavTable],
                        'FIND_IN_SET(main_table.bodyconcern_id, cev.value) AND cev.attribute_id = ' . (int)$attributeId,
                        []
                    )->group('main_table.bodyconcern_id');

                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Could not filter by product bodyconcerns: %1', $e->getMessage())
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
            $bodyconcernItems = [];
            foreach ($items as $item) {
                $bodyconcernItems[] = [
                    'bodyconcern_id' => $item->getBodyConcernId(),
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
            $searchResults->setItems($bodyconcernItems);
            $searchResults->setTotalCount($collection->getSize());
            
            return $searchResults;
            
        } catch (\Exception $e) {
            // Add error logging here
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not retrieve bodyconcerns: %1', $e->getMessage())
            );
        }
    }

    public function delete(BodyConcernInterface $bodyconcern)
    {
        try {
            $this->resource->delete($bodyconcern);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($bodyconcernId)
    {
        return $this->delete($this->getById($bodyconcernId));
    }

    public function update($bodyconcernId, BodyConcernInterface $bodyconcern)
    {
        try {
            // Load existing bodyconcern
            $existingBodyConcern = $this->getById($bodyconcernId);
            
            $existingBodyConcern->setName($bodyconcern->getName());
            $existingBodyConcern->setDescription($bodyconcern->getDescription());
            $existingBodyConcern->setLogo($bodyconcern->getLogo());
            $existingBodyConcern->setTags($bodyconcern->getTags());

            $this->resource->save($existingBodyConcern);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                 new \Magento\Framework\Phrase('Unable to update bodyconcern: %1', [$exception->getMessage()])
            );
        }   

        return $existingBodyConcern;
    }

}