<?php
namespace Formula\CartItemExtension\Api\Data;

/**
 * Product media data for cart item extension attributes
 */
interface ProductMediaInterface
{
    /**
     * Get media ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set media ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get media file path
     *
     * @return string|null
     */
    public function getFile();

    /**
     * Set media file path
     *
     * @param string $file
     * @return $this
     */
    public function setFile($file);
}

