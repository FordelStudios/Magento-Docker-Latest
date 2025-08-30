<?php
declare(strict_types=1);

namespace Formula\HomeContent\Api\Data;

interface KoreanIngredientInterface
{
    /**
     * Get ingredient ID
     *
     * @return string
     */
    public function getIngredientId();

    /**
     * Set ingredient ID
     *
     * @param string $ingredientId
     * @return $this
     */
    public function setIngredientId($ingredientId);

    /**
     * Get image
     *
     * @return string
     */
    public function getImage();

    /**
     * Set image
     *
     * @param string $image
     * @return $this
     */
    public function setImage($image);
}