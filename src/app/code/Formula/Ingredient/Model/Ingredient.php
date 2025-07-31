<?php

namespace Formula\Ingredient\Model;

use Formula\Ingredient\Api\Data\IngredientInterface;
use Magento\Framework\Model\AbstractModel;

class Ingredient extends AbstractModel implements IngredientInterface
{
    /**
     * @var string
     */
    const INGREDIENT_ID = 'ingredient_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const LOGO = 'logo';
    const BENEFITS = 'benefits';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const COUNTRY_ID = 'country_id'; 

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $registry);
        $this->jsonSerializer = $jsonSerializer;
    }

    protected function _construct()
    {
        $this->_init(\Formula\Ingredient\Model\ResourceModel\Ingredient::class);
    }

    /**
     * Get Ingredient Id
     * 
     * @return int|null
     */
    public function getIngredientId()
    {
        return $this->getData(self::INGREDIENT_ID);
    }

    /**
     * Set Ingredient Id
     * 
     * @param int $ingredientId
     * @return $this
     */
    public function setIngredientId($ingredientId)
    {
        return $this->setData(self::INGREDIENT_ID, $ingredientId);
    }

    /**
     * Get Ingredient Name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set Ingredient Name
     * 
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get Ingredient Description
     * 
     * @return string|null
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Set Ingredient Description
     * 
     * @param string|null $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }



    /**
     * Get Ingredient Logo Path
     * 
     * @return string|null
     */
    public function getLogo()
    {
        return $this->getData(self::LOGO);
    }

    /**
     * Set Ingredient Logo Path
     * 
     * @param string|null $logo
     * @return $this
     */
    public function setLogo($logo)
    {
        return $this->setData(self::LOGO, $logo);
    }

    

    /**
     * Get Benefits
     * 
     * @return string|null
     */
    public function getBenefits()
    {
        $benefits = $this->getData(self::BENEFITS);
        if ($benefits && is_string($benefits)) {
            try {
                return $this->jsonSerializer->unserialize($benefits);
            } catch (\Exception $e) {
                return [];
            }
        }
        return $benefits ?: [];
    }

    /**
     * Set Benefits
     * 
     * @param string|mixed[] $benefits
     * @return $this
     */
    public function setBenefits($benefits)
    {
        if (is_array($benefits)) {
            $benefits = $this->jsonSerializer->serialize($benefits);
        }
        return $this->setData(self::BENEFITS, $benefits);
    }


    /**
     * Get Ingredient Creation Time
     * 
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set Ingredient Creation Time
     * 
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get Ingredient Update Time
     * 
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set Ingredient Update Time
     * 
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * {@inheritdoc}
     */
    public function getCountryId() 
    {
        return $this->getData(self::COUNTRY_ID);
    }


    /**
     * {@inheritdoc}
     */
    public function setCountryId($countryId) 
    {
        return $this->setData(self::COUNTRY_ID, $countryId);
    }
}