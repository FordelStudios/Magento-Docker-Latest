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

class SkinConcernRepository implements SkinConcernRepositoryInterface
{
    protected $resource;
    protected $skinconcernFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;

    public function __construct(
        ResourceSkinConcern $resource,
        SkinConcernFactory $skinconcernFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->skinconcernFactory = $skinconcernFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function save(SkinConcernInterface $skinconcern)
    {
        try {
            $skinconcern->setPromotionalBanners(json_encode($skinconcern->getPromotionalBanners()));
            $skinconcern->setTags(json_encode($skinconcern->getTags()));

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
                    'status' => $item->getStatus(),
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
            
            // Update fields
            $existingSkinConcern->setName($skinconcern->getName());
            $existingSkinConcern->setDescription($skinconcern->getDescription());
            $existingSkinConcern->setTagline($skinconcern->getTagline());
            $existingSkinConcern->setLogo($skinconcern->getLogo());
            $existingSkinConcern->setPromotionalBanners($skinconcern->getPromotionalBanners());
            $existingSkinConcern->setTags($skinconcern->getTags());
            $existingSkinConcern->setStatus($skinconcern->getStatus());

            // Save the updated skinconcern
            $this->resource->save($existingSkinConcern);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                 new \Magento\Framework\Phrase('Unable to update skinconcern: %1', [$exception->getMessage()])
            );
        }   

        return $existingSkinConcern;
    }

}