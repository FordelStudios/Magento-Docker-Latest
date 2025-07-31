<?php
// app/code/Formula/CategoryBanners/Api/Data/SubcategoryInterface.php
namespace Formula\CategoryBanners\Api\Data;

/**
 * @api
 */
interface SubcategoryInterface
{
    const ID = 'id';
    const NAME = 'name';

    /**
     * Get subcategory ID
     *
     * @return int
     */
    public function getId();

    /**
     * Set subcategory ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get subcategory name
     *
     * @return string
     */
    public function getName();

    /**
     * Set subcategory name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);
}