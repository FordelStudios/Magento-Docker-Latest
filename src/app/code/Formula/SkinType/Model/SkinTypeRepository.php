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

class SkinTypeRepository implements SkinTypeRepositoryInterface
{
    protected $resource;
    protected $skintypeFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;

    public function __construct(
        ResourceSkinType $resource,
        SkinTypeFactory $skintypeFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->skintypeFactory = $skintypeFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function save(SkinTypeInterface $skintype)
    {
        try {
            $skintype->setPromotionalBanners(json_encode($skintype->getPromotionalBanners()));
            $skintype->setTags(json_encode($skintype->getTags()));

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
                    'status' => $item->getStatus(),
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
            // Load existing skintype
            $existingSkinType = $this->getById($skintypeId);
            
            // Update fields
            $existingSkinType->setName($skintype->getName());
            $existingSkinType->setDescription($skintype->getDescription());
            $existingSkinType->setTagline($skintype->getTagline());
            $existingSkinType->setLogo($skintype->getLogo());
            $existingSkinType->setPromotionalBanners($skintype->getPromotionalBanners());
            $existingSkinType->setTags($skintype->getTags());
            $existingSkinType->setStatus($skintype->getStatus());

            // Save the updated skintype
            $this->resource->save($existingSkinType);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                 new \Magento\Framework\Phrase('Unable to update skintype: %1', [$exception->getMessage()])
            );
        }   

        return $existingSkinType;
    }

}