<?php
namespace Formula\HairConcern\Model;

use Formula\HairConcern\Api\HairConcernRepositoryInterface;
use Formula\HairConcern\Api\Data\HairConcernInterface;
use Formula\HairConcern\Model\ResourceModel\HairConcern as ResourceHairConcern;
use Formula\HairConcern\Model\ResourceModel\HairConcern\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class HairConcernRepository implements HairConcernRepositoryInterface
{
    protected $resource;
    protected $hairconcernFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;
    protected $request;
    protected $eavConfig;

    public function __construct(
        ResourceHairConcern $resource,
        HairConcernFactory $hairconcernFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        EavConfig $eavConfig,
        RequestInterface $request
    ) {
        $this->resource = $resource;
        $this->hairconcernFactory = $hairconcernFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->request = $request;
        $this->eavConfig = $eavConfig;
    }

    public function save(HairConcernInterface $hairconcern)
    {
        try {
            $this->resource->save($hairconcern);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $hairconcern;
    }

    public function getById($hairconcernId)
    {
        $hairconcern = $this->hairconcernFactory->create();
        $this->resource->load($hairconcern, $hairconcernId);
        if (!$hairconcern->getId()) {
            throw new NoSuchEntityException(new \Magento\Framework\Phrase('HairConcern with id %1 does not exist.', [$hairconcernId]));
        }
        return $hairconcern;
    }

    public function getList(SearchCriteriaInterface $searchCriteria){
        try {
            $collection = $this->collectionFactory->create();

            // Check for onlyIncludeWithProducts parameter
            $onlyIncludeWithProducts = $this->request->getParam('onlyIncludeWithProducts');
            
            if ($onlyIncludeWithProducts === 'true' || $onlyIncludeWithProducts === '1') {
                try {
                    // Get attribute ID for 'hairconcern'
                    $hairconcernAttribute = $this->eavConfig->getAttribute(
                        \Magento\Catalog\Model\Product::ENTITY,
                        'hairconcern'
                    );

                    $attributeId = $hairconcernAttribute->getAttributeId();

                    // Get EAV varchar table
                    $productEavTable = $collection->getTable('catalog_product_entity_varchar');

                    // Join using FIND_IN_SET for multiselect values
                    $collection->getSelect()->joinInner(
                        ['cev' => $productEavTable],
                        'FIND_IN_SET(main_table.hairconcern_id, cev.value) AND cev.attribute_id = ' . (int)$attributeId,
                        []
                    )->group('main_table.hairconcern_id');

                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Could not filter by product hairconcerns: %1', $e->getMessage())
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
            $hairconcernItems = [];
            foreach ($items as $item) {
                $hairconcernItems[] = [
                    'hairconcern_id' => $item->getHairConcernId(),
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
            $searchResults->setItems($hairconcernItems);
            $searchResults->setTotalCount($collection->getSize());
            
            return $searchResults;
            
        } catch (\Exception $e) {
            // Add error logging here
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not retrieve hairconcerns: %1', $e->getMessage())
            );
        }
    }

    public function delete(HairConcernInterface $hairconcern)
    {
        try {
            $this->resource->delete($hairconcern);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($hairconcernId)
    {
        return $this->delete($this->getById($hairconcernId));
    }

    public function update($hairconcernId, HairConcernInterface $hairconcern)
    {
        try {
            // Load existing hairconcern
            $existingHairConcern = $this->getById($hairconcernId);
            
            $existingHairConcern->setName($hairconcern->getName());
            $existingHairConcern->setDescription($hairconcern->getDescription());
            $existingHairConcern->setLogo($hairconcern->getLogo());
            $existingHairConcern->setTags($hairconcern->getTags());

            $this->resource->save($existingHairConcern);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                 new \Magento\Framework\Phrase('Unable to update hairconcern: %1', [$exception->getMessage()])
            );
        }   

        return $existingHairConcern;
    }

}