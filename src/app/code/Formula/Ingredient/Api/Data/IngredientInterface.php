<?php
namespace Formula\Ingredient\Api\Data;

interface IngredientInterface
{
    const INGREDIENT_ID = 'ingredient_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const LOGO = 'logo';
    const BENEFITS = 'benefits';
    const IS_KOREAN = 'is_korean';

    /**
     * @return int|null
     */
    public function getIngredientId();

    /**
     * @param int $ingredientId
     * @return $this
     */
    public function setIngredientId($ingredientId);

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getDescription();

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description);


    /**
     * @return string|null
     */
    public function getLogo();

    /**
     * @param string $logo
     * @return $this
     */
    public function setLogo($logo);



    /**
     * @return string|null
     */
    public function getBenefits();

    /**
     * @param string|mixed[] $benefits
     * @return $this
     */
    public function setBenefits($benefits);


    /**
     * @return bool|null
     */
    public function getIsKorean();

    /**
     * @param bool $isKorean
     * @return $this
     */
    public function setIsKorean($isKorean);


}