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
            $brand->setTags(json_encode($brand->getTags()));

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
        return $brand;
    }

    public function getList(SearchCriteriaInterface $searchCriteria){
        try {
            $collection = $this->collectionFactory->create();
            
            // Get raw items from collection
            $items = $collection->getItems();
            
            // Convert items to array format
            $brandItems = [];
            foreach ($items as $item) {
                $brandItems[] = [
                    'brand_id' => $item->getBrandId(),
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
            
            // Update fields
            $existingBrand->setName($brand->getName());
            $existingBrand->setDescription($brand->getDescription());
            $existingBrand->setTagline($brand->getTagline());
            $existingBrand->setLogo($brand->getLogo());
            $existingBrand->setPromotionalBanners($brand->getPromotionalBanners());
            $existingBrand->setTags($brand->getTags());
            $existingBrand->setStatus($brand->getStatus());

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