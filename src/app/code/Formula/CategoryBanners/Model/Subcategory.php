<?php
// app/code/Formula/CategoryBanners/Model/Subcategory.php
namespace Formula\CategoryBanners\Model;

use Formula\CategoryBanners\Api\Data\SubcategoryInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class Subcategory extends AbstractExtensibleModel implements SubcategoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }
}