<?php
namespace Formula\Ingredient\Model;

use Formula\Ingredient\Api\IngredientRepositoryInterface;
use Formula\Ingredient\Api\Data\IngredientInterface;
use Formula\Ingredient\Model\ResourceModel\Ingredient as ResourceIngredient;
use Formula\Ingredient\Model\ResourceModel\Ingredient\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;
use Magento\Eav\Model\Config as EavConfig;



class IngredientRepository implements IngredientRepositoryInterface
{
    protected $resource;
    protected $ingredientFactory;
    protected $collectionFactory;
    protected $searchResultsFactory;
    protected $request;
    protected $eavConfig;
    protected $searchCriteriaBuilder;
    protected $filterBuilder;
    protected $filterGroupBuilder;


    public function __construct(
        ResourceIngredient $resource,
        IngredientFactory $ingredientFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        EavConfig $eavConfig,
        RequestInterface $request,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->resource = $resource;
        $this->ingredientFactory = $ingredientFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->request = $request;
        $this->eavConfig = $eavConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
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

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        try {
            $collection = $this->collectionFactory->create();

           // Debug: Log what search criteria we're receiving
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/ingredient_debug.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Search Criteria Filter Groups Count: ' . count($searchCriteria->getFilterGroups()));
            
            // Manual SearchCriteria building from request parameters
            $searchCriteriaData = $this->request->getParam('searchCriteria', []);
            $logger->info('Raw searchCriteria param: ' . json_encode($searchCriteriaData));
            
            // Build filters from request if searchCriteria is empty
            $manualSearchCriteria = null;
            if (count($searchCriteria->getFilterGroups()) == 0 && !empty($searchCriteriaData)) {
                $manualSearchCriteria = $this->buildSearchCriteriaFromRequest($searchCriteriaData, $collection, $logger);
            }
            
            // Apply search criteria filters FIRST
            foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
                $logger->info('Processing filter group with ' . count($filterGroup->getFilters()) . ' filters');
                foreach ($filterGroup->getFilters() as $filter) {
                    $condition = $filter->getConditionType() ?: 'eq';
                    $field = $filter->getField();
                    $value = $filter->getValue();
                    
                    $logger->info("Filter: field={$field}, value={$value}, condition={$condition}");
                    
                    // Map 'country' to 'country_id' for backward compatibility
                    if ($field === 'country') {
                        $field = 'country_id';
                    }
                    
                    $collection->addFieldToFilter($field, [$condition => $value]);
                }
            }

            // Also check for direct URL parameters as fallback
            $countryId = $this->request->getParam('country_id');
            if ($countryId) {
                $collection->addFieldToFilter('country_id', ['eq' => $countryId]);
                $logger->info("Applied direct country_id filter: {$countryId}");
            }

            
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
            
            // Apply pagination - handle both from SearchCriteria and manual parsing
            $currentPage = $searchCriteria->getCurrentPage();
            $pageSize = $searchCriteria->getPageSize();

            // If pagination is not set in SearchCriteria, check manual searchCriteria data
            if (!$currentPage && !$pageSize && !empty($searchCriteriaData)) {
                $currentPage = $searchCriteriaData['currentPage'] ?? $searchCriteriaData['current_page'] ?? 1;
                $pageSize = $searchCriteriaData['pageSize'] ?? $searchCriteriaData['page_size'] ?? null;
                $logger->info("Manual pagination: currentPage={$currentPage}, pageSize={$pageSize}");
            }


            if ($currentPage) {
                $collection->setCurPage($currentPage);
            }
            if ($pageSize) {
                $collection->setPageSize($pageSize);
            }
            
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
            $searchResults->setSearchCriteria($manualSearchCriteria ?: $searchCriteria);
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


    /**
     * Build search criteria from request parameters
     *
     * @param array $searchCriteriaData
     * @param \Formula\Ingredient\Model\ResourceModel\Ingredient\Collection $collection
     * @param \Zend_Log $logger
     * @return \Magento\Framework\Api\SearchCriteriaInterface|null
     */
    private function buildSearchCriteriaFromRequest($searchCriteriaData, $collection, $logger)
    {
        // Handle case where searchCriteria only contains pagination but filters are in separate params
        $allParams = $this->request->getParams();
        $logger->info('All request params: ' . json_encode($allParams));
        
        // Check if filterGroups exist in searchCriteria or as separate parameters
        $filterGroups = [];
        
        if (isset($searchCriteriaData['filterGroups'])) {
            $filterGroups = $searchCriteriaData['filterGroups'];
        } elseif (isset($searchCriteriaData['filter_groups'])) {
            $filterGroups = $searchCriteriaData['filter_groups'];
        } else {
            // Check if filters are passed as separate searchCriteria parameters
            foreach ($allParams as $key => $value) {
                if (strpos($key, 'searchCriteria[filterGroups]') === 0 || 
                    strpos($key, 'searchCriteria[filter_groups]') === 0) {
                    // Parse the complex parameter structure
                    $filterGroups = $this->parseFilterGroupsFromParams($allParams, $logger);
                    break;
                }
            }
        }
        
        $logger->info('Filter groups found: ' . json_encode($filterGroups));
        
        // Create a new SearchCriteria object
        $searchCriteriaBuilder = $this->searchCriteriaBuilder;
        $builtFilterGroups = [];
        
        // Apply filters and build proper SearchCriteria
        foreach ($filterGroups as $filterGroup) {
            if (isset($filterGroup['filters'])) {
                $filters = [];
                foreach ($filterGroup['filters'] as $filter) {
                    $field = $filter['field'] ?? '';
                    $value = $filter['value'] ?? '';
                    $conditionType = $filter['conditionType'] ?? $filter['condition_type'] ?? 'eq';
                    
                    if ($field && $value !== '') {
                        $logger->info("Manual Filter: field={$field}, value={$value}, condition={$conditionType}");
                        
                        $originalField = $field;
                        // Map 'country' to 'country_id' for backward compatibility
                        if ($field === 'country') {
                            $field = 'country_id';
                        }
                        
                        $collection->addFieldToFilter($field, [$conditionType => $value]);
                        
                        // Build filter for SearchCriteria response (use original field name)
                        $filterObj = $this->filterBuilder
                            ->setField($originalField)
                            ->setValue($value)
                            ->setConditionType($conditionType)
                            ->create();
                        $filters[] = $filterObj;
                    }
                }
                
                if (!empty($filters)) {
                    $filterGroupObj = $this->filterGroupBuilder
                        ->setFilters($filters)
                        ->create();
                    $builtFilterGroups[] = $filterGroupObj;
                }
            }
        }
        
        // Set filter groups
        if (!empty($builtFilterGroups)) {
            $searchCriteriaBuilder->setFilterGroups($builtFilterGroups);
        }
        
        // Set pagination
        if (isset($searchCriteriaData['currentPage'])) {
            $searchCriteriaBuilder->setCurrentPage($searchCriteriaData['currentPage']);
        }
        if (isset($searchCriteriaData['pageSize'])) {
            $searchCriteriaBuilder->setPageSize($searchCriteriaData['pageSize']);
        }
        
        return $searchCriteriaBuilder->create();
    }
    
    /**
     * Parse filter groups from flat parameter structure
     *
     * @param array $params
     * @param \Zend_Log $logger
     * @return array
     */
    private function parseFilterGroupsFromParams($params, $logger)
    {
        $filterGroups = [];
        
        foreach ($params as $key => $value) {
            // Match patterns like: searchCriteria[filterGroups][0][filters][0][field]
            if (preg_match('/searchCriteria\[(?:filterGroups|filter_groups)\]\[(\d+)\]\[filters\]\[(\d+)\]\[(\w+)\]/', $key, $matches)) {
                $groupIndex = (int)$matches[1];
                $filterIndex = (int)$matches[2];
                $property = $matches[3];
                
                $logger->info("Parsed filter param: group={$groupIndex}, filter={$filterIndex}, property={$property}, value={$value}");
                
                if (!isset($filterGroups[$groupIndex])) {
                    $filterGroups[$groupIndex] = ['filters' => []];
                }
                
                if (!isset($filterGroups[$groupIndex]['filters'][$filterIndex])) {
                    $filterGroups[$groupIndex]['filters'][$filterIndex] = [];
                }
                
                $filterGroups[$groupIndex]['filters'][$filterIndex][$property] = $value;
            }
        }
        
        // Convert to sequential array
        return array_values($filterGroups);
    }
}