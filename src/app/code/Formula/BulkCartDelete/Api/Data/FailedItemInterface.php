<?php declare(strict_types=1);

// File: Api/Data/FailedItemInterface.php

namespace Formula\BulkCartDelete\Api\Data;

/**
 * Interface for failed item details
 */
interface FailedItemInterface
{
    /**
     * Get item ID
     *
     * @return int
     */
    public function getItemId(): int;

    /**
     * Set item ID
     *
     * @param int $itemId
     * @return $this
     */
    public function setItemId(int $itemId): self;

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

