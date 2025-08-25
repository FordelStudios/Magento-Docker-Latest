<?php
namespace Formula\FaceConcern\Model;

use Formula\FaceConcern\Api\FaceConcernRepositoryInterface;
use Formula\FaceConcern\Api\Data\FaceConcernInterface;
use Formula\FaceConcern\Model\ResourceModel\FaceConcern as ResourceFaceConcern;
use Formula\FaceConcern\Model\ResourceModel\FaceConcern\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class FaceConcernRepository implements FaceConcernRepositoryInterface
{
    protected $resource;
    protected $faceconcernFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;
    protected $request;
    protected $eavConfig;

    public function __construct(
        ResourceFaceConcern $resource,
        FaceConcernFactory $faceconcernFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        EavConfig $eavConfig,
        RequestInterface $request
    ) {
        $this->resource = $resource;
        $this->faceconcernFactory = $faceconcernFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->request = $request;
        $this->eavConfig = $eavConfig;
    }

    public function save(FaceConcernInterface $faceconcern)
    {
        try {
            $this->resource->save($faceconcern);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $faceconcern;
    }

    public function getById($faceconcernId)
    {
        $faceconcern = $this->faceconcernFactory->create();
        $this->resource->load($faceconcern, $faceconcernId);
        if (!$faceconcern->getId()) {
            throw new NoSuchEntityException(new \Magento\Framework\Phrase('FaceConcern with id %1 does not exist.', [$faceconcernId]));
        }
        return $faceconcern;
    }

    public function getList(SearchCriteriaInterface $searchCriteria){
        try {
            $collection = $this->collectionFactory->create();

            // Check for onlyIncludeWithProducts parameter
            $onlyIncludeWithProducts = $this->request->getParam('onlyIncludeWithProducts');
            
            if ($onlyIncludeWithProducts === 'true' || $onlyIncludeWithProducts === '1') {
                try {
                    // Get attribute ID for 'faceconcern'
                    $faceconcernAttribute = $this->eavConfig->getAttribute(
                        \Magento\Catalog\Model\Product::ENTITY,
                        'faceconcern'
                    );

                    $attributeId = $faceconcernAttribute->getAttributeId();

                    // Get EAV varchar table
                    $productEavTable = $collection->getTable('catalog_product_entity_varchar');

                    // Join using FIND_IN_SET for multiselect values
                    $collection->getSelect()->joinInner(
                        ['cev' => $productEavTable],
                        'FIND_IN_SET(main_table.faceconcern_id, cev.value) AND cev.attribute_id = ' . (int)$attributeId,
                        []
                    )->group('main_table.faceconcern_id');

                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Could not filter by product faceconcerns: %1', $e->getMessage())
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
            $faceconcernItems = [];
            foreach ($items as $item) {
                $faceconcernItems[] = [
                    'faceconcern_id' => $item->getFaceConcernId(),
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
            $searchResults->setItems($faceconcernItems);
            $searchResults->setTotalCount($collection->getSize());
            
            return $searchResults;
            
        } catch (\Exception $e) {
            // Add error logging here
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not retrieve faceconcerns: %1', $e->getMessage())
            );
        }
    }

    public function delete(FaceConcernInterface $faceconcern)
    {
        try {
            $this->resource->delete($faceconcern);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($faceconcernId)
    {
        return $this->delete($this->getById($faceconcernId));
    }

    public function update($faceconcernId, FaceConcernInterface $faceconcern)
    {
        try {
            // Load existing faceconcern
            $existingFaceConcern = $this->getById($faceconcernId);
            
            $existingFaceConcern->setName($faceconcern->getName());
            $existingFaceConcern->setDescription($faceconcern->getDescription());
            $existingFaceConcern->setLogo($faceconcern->getLogo());
            $existingFaceConcern->setTags($faceconcern->getTags());

            $this->resource->save($existingFaceConcern);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                 new \Magento\Framework\Phrase('Unable to update faceconcern: %1', [$exception->getMessage()])
            );
        }   

        return $existingFaceConcern;
    }

}