<?php
namespace Formula\SkinType\Model;

use Formula\SkinType\Api\SkinTypeRepositoryInterface;
use Formula\SkinType\Api\Data\SkinTypeInterface;
use Formula\SkinType\Model\ResourceModel\SkinType as ResourceSkinType;
use Formula\SkinType\Model\ResourceModel\SkinType\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class SkinTypeRepository implements SkinTypeRepositoryInterface
{
    protected $resource;
    protected $skintypeFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;
    protected $request;
    protected $eavConfig;

    public function __construct(
        ResourceSkinType $resource,
        SkinTypeFactory $skintypeFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        EavConfig $eavConfig,
        RequestInterface $request
    ) {
        $this->resource = $resource;
        $this->skintypeFactory = $skintypeFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->request = $request;
        $this->eavConfig = $eavConfig;
    }

    public function save(SkinTypeInterface $skintype)
    {
        try {
            $this->resource->save($skintype);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $skintype;
    }

    public function getById($skintypeId)
    {
        $skintype = $this->skintypeFactory->create();
        $this->resource->load($skintype, $skintypeId);
        if (!$skintype->getId()) {
            throw new NoSuchEntityException(new \Magento\Framework\Phrase('SkinType with id %1 does not exist.', [$skintypeId]));
        }
        return $skintype;
    }

    public function getList(SearchCriteriaInterface $searchCriteria){
        try {
            $collection = $this->collectionFactory->create();

            // Check for onlyIncludeWithProducts parameter
            $onlyIncludeWithProducts = $this->request->getParam('onlyIncludeWithProducts');
            
            if ($onlyIncludeWithProducts === 'true' || $onlyIncludeWithProducts === '1') {
                try {
                    // Get attribute ID for 'skintype'
                    $skintypeAttribute = $this->eavConfig->getAttribute(
                        \Magento\Catalog\Model\Product::ENTITY,
                        'skintype'
                    );

                    $attributeId = $skintypeAttribute->getAttributeId();

                    // Get EAV varchar table
                    $productEavTable = $collection->getTable('catalog_product_entity_varchar');

                    // Join using FIND_IN_SET for multiselect values
                    $collection->getSelect()->joinInner(
                        ['cev' => $productEavTable],
                        'FIND_IN_SET(main_table.skintype_id, cev.value) AND cev.attribute_id = ' . (int)$attributeId,
                        []
                    )->group('main_table.skintype_id');

                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Could not filter by product skintypes: %1', $e->getMessage())
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
            $skintypeItems = [];
            foreach ($items as $item) {
                $skintypeItems[] = [
                    'skintype_id' => $item->getSkinTypeId(),
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
            $searchResults->setItems($skintypeItems);
            $searchResults->setTotalCount($collection->getSize());
            
            return $searchResults;
            
        } catch (\Exception $e) {
            // Add error logging here
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not retrieve skintypes: %1', $e->getMessage())
            );
        }
    }

    public function delete(SkinTypeInterface $skintype)
    {
        try {
            $this->resource->delete($skintype);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($skintypeId)
    {
        return $this->delete($this->getById($skintypeId));
    }

    public function update($skintypeId, SkinTypeInterface $skintype)
    {
        try {
            $existingSkinType = $this->getById($skintypeId);
            
            $existingSkinType->setName($skintype->getName());
            $existingSkinType->setDescription($skintype->getDescription());
            $existingSkinType->setLogo($skintype->getLogo());
            $existingSkinType->setTags($skintype->getTags());

            $this->resource->save($existingSkinType);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                 new \Magento\Framework\Phrase('Unable to update skintype: %1', [$exception->getMessage()])
            );
        }   

        return $existingSkinType;
    }

}