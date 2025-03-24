<?php
namespace Formula\Ingredient\Model;

use Formula\Ingredient\Api\IngredientRepositoryInterface;
use Formula\Ingredient\Api\Data\IngredientInterface;
use Formula\Ingredient\Model\ResourceModel\Ingredient as ResourceIngredient;
use Formula\Ingredient\Model\ResourceModel\Ingredient\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class IngredientRepository implements IngredientRepositoryInterface
{
    protected $resource;
    protected $ingredientFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;

    public function __construct(
        ResourceIngredient $resource,
        IngredientFactory $ingredientFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->resource = $resource;
        $this->ingredientFactory = $ingredientFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    public function save(IngredientInterface $ingredient)
    {
        try {
            $ingredient->setPromotionalBanners(json_encode($ingredient->getPromotionalBanners()));
            $ingredient->setTags(json_encode($ingredient->getTags()));

            $this->resource->save($ingredient);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $ingredient;
    }

    public function getById($ingredientId)
    {
        $ingredient = $this->ingredientFactory->create();
        $this->resource->load($ingredient, $ingredientId);
        if (!$ingredient->getId()) {
            throw new NoSuchEntityException(new \Magento\Framework\Phrase('Ingredient with id %1 does not exist.', [$ingredientId]));
        }
        return $ingredient;
    }

    public function getList(SearchCriteriaInterface $searchCriteria){
        try {
            $collection = $this->collectionFactory->create();
            
            // Get raw items from collection
            $items = $collection->getItems();
            
            // Convert items to array format
            $ingredientItems = [];
            foreach ($items as $item) {
                $ingredientItems[] = [
                    'ingredient_id' => $item->getIngredientId(),
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
            $searchResults->setItems($ingredientItems);
            $searchResults->setTotalCount($collection->getSize());
            
            return $searchResults;
            
        } catch (\Exception $e) {
            // Add error logging here
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Could not retrieve ingredients: %1', $e->getMessage())
            );
        }
    }

    public function delete(IngredientInterface $ingredient)
    {
        try {
            $this->resource->delete($ingredient);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($ingredientId)
    {
        return $this->delete($this->getById($ingredientId));
    }

    public function update($ingredientId, IngredientInterface $ingredient)
    {
        try {
            // Load existing ingredient
            $existingIngredient = $this->getById($ingredientId);
            
            // Update fields
            $existingIngredient->setName($ingredient->getName());
            $existingIngredient->setDescription($ingredient->getDescription());
            $existingIngredient->setTagline($ingredient->getTagline());
            $existingIngredient->setLogo($ingredient->getLogo());
            $existingIngredient->setPromotionalBanners($ingredient->getPromotionalBanners());
            $existingIngredient->setTags($ingredient->getTags());
            $existingIngredient->setStatus($ingredient->getStatus());

            // Save the updated ingredient
            $this->resource->save($existingIngredient);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                 new \Magento\Framework\Phrase('Unable to update ingredient: %1', [$exception->getMessage()])
            );
        }   

        return $existingIngredient;
    }

}