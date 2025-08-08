<?php
namespace Formula\BulkCartItemsAdd\Api\Data;

/**
 * Interface for failed item details in bulk add
 */
interface FailedItemInterface
{
    /**
     * Get SKU of the failed item
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set SKU of the failed item
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * Get error message
     *
     * @return string
     */
    public function getError(): string;

    /**
     * Set error message
     *
     * @param string $error
     * @return $this
     */
    public function setError(string $error): self;
}
