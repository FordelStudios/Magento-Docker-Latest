<?php
namespace Formula\Ingredient\Api\Data;

interface IngredientInterface
{
    const INGREDIENT_ID = 'ingredient_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const LOGO = 'logo';
    const BENEFITS = 'benefits';
    const COUNTRY_ID = 'country_id'; 

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
     * Get country ID
     *
     * @return string
     */
    public function getCountryId();  

    /**
     * Set country ID
     *
     * @param string $countryId  
     * @return $this
     */
    public function setCountryId($countryId);  


}