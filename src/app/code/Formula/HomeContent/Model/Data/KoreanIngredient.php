<?php
declare(strict_types=1);

namespace Formula\HomeContent\Model\Data;

use Formula\HomeContent\Api\Data\KoreanIngredientInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class KoreanIngredient extends AbstractExtensibleObject implements KoreanIngredientInterface
{
    const INGREDIENT_ID = 'ingredient_id';
    const IMAGE = 'image';

    public function getIngredientId()
    {
        return $this->_get(self::INGREDIENT_ID) ?: '';
    }

    public function setIngredientId($ingredientId)
    {
        return $this->setData(self::INGREDIENT_ID, $ingredientId);
    }

    public function getImage()
    {
        return $this->_get(self::IMAGE) ?: '';
    }

    public function setImage($image)
    {
        return $this->setData(self::IMAGE, $image);
    }
}