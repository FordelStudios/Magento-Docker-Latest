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
use Magento\Framework\App\RequestInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;


class IngredientRepository implements IngredientRepositoryInterface
{
    protected $resource;
    protected $ingredientFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;
    protected $request;
    protected $eavConfig;


    public function __construct(
        ResourceIngredient $resource,
        IngredientFactory $ingredientFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        EavConfig $eavConfig,
        RequestInterface $request
    ) {
        $this->resource = $resource;
        $this->ingredientFactory = $ingredientFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->request = $request;
        $this->eavConfig = $eavConfig;
    }

    public function save(IngredientInterface $ingredient)
    {
        try {

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

            // Check for onlyIncludeWithProducts parameter
            $onlyIncludeWithProducts = $this->request->getParam('onlyIncludeWithProducts');
            
            if ($onlyIncludeWithProducts === 'true' || $onlyIncludeWithProducts === '1') {
                try {
                    // Get attribute ID for 'ingredient'
                    $ingredientAttribute = $this->eavConfig->getAttribute(
                        \Magento\Catalog\Model\Product::ENTITY,
                        'ingredient'
                    );

                    $attributeId = $ingredientAttribute->getAttributeId();

                    // Get EAV varchar table
                    $productEavTable = $collection->getTable('catalog_product_entity_varchar');

                    // Join using FIND_IN_SET for multiselect values
                    $collection->getSelect()->joinInner(
                        ['cev' => $productEavTable],
                        'FIND_IN_SET(main_table.ingredient_id, cev.value) AND cev.attribute_id = ' . (int)$attributeId,
                        []
                    )->group('main_table.ingredient_id');

                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Could not filter by product ingredients: %1', $e->getMessage())
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
            $ingredientItems = [];
            foreach ($items as $item) {
                $ingredientItems[] = [
                    'ingredient_id' => $item->getIngredientId(),
                    'name' => $item->getName(),
                    'description' => $item->getDescription(),
                    'logo' => $item->getLogo(),
                    'benefits' => $item->getBenefits(),
                    'created_at' => $item->getCreatedAt(),
                    'updated_at' => $item->getUpdatedAt(),
                    'country_id' => $item->getCountryId(),
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
            $existingIngredient->setLogo($ingredient->getLogo());
            $existingIngredient->setBenefits($ingredient->getBenefits());
            $existingIngredient->setCountryId($ingredient->getCountryId());

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