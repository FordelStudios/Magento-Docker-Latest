<?php
namespace Formula\Ingredient\Api;

use Formula\Ingredient\Api\Data\IngredientInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface IngredientRepositoryInterface
{
    /**
     * Save ingredient.
     *
     * @param IngredientInterface $ingredient
     * @return IngredientInterface
     */
    public function save(IngredientInterface $ingredient);

    /**
     * Get ingredient by ID.
     *
     * @param int $ingredientId
     * @return IngredientInterface
     */
    public function getById($ingredientId);

    /**
     * Get ingredient list.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete ingredient.
     *
     * @param IngredientInterface $ingredient
     * @return bool
     */
    public function delete(IngredientInterface $ingredient);

    /**
     * Delete ingredient by ID.
     *
     * @param int $ingredientId
     * @return bool
     */
    public function deleteById($ingredientId);

    /**
     * Update ingredient.
     *
     * @param int $ingredientId
     * @param IngredientInterface $ingredient
     * @return IngredientInterface
     */
    public function update($ingredientId, IngredientInterface $ingredient);

}