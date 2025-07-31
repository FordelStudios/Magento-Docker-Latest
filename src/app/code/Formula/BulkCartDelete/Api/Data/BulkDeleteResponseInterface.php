<?php 
// File: Api/Data/BulkDeleteResponseInterface.php
namespace Formula\BulkCartDelete\Api\Data;

/**
 * Interface for bulk cart delete response
 */
interface BulkDeleteResponseInterface
{
    /**
     * Get success status
     *
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * Set success status
     *
     * @param bool $success
     * @return $this
     */
    public function setSuccess(bool $success): self;

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self;

    /**
     * Get total requested items
     *
     * @return int
     */
    public function getTotalRequested(): int;

    /**
     * Set total requested items
     *
     * @param int $total
     * @return $this
     */
    public function setTotalRequested(int $total): self;

    /**
     * Get successfully deleted count
     *
     * @return int
     */
    public function getSuccessfullyDeleted(): int;

    /**
     * Set successfully deleted count
     *
     * @param int $count
     * @return $this
     */
    public function setSuccessfullyDeleted(int $count): self;

    /**
     * Get failed count
     *
     * @return int
     */
    public function getFailed(): int;

    /**
     * Set failed count
     *
     * @param int $count
     * @return $this
     */
    public function setFailed(int $count): self;

    /**
     * Get failed items details
     *
     * @return \Formula\BulkCartDelete\Api\Data\FailedItemInterface[]
     */
    public function getFailedItems(): array;

    /**
     * Set failed items details
     *
     * @param \Formula\BulkCartDelete\Api\Data\FailedItemInterface[] $failedItems
     * @return $this
     */
    public function setFailedItems(array $failedItems): self;

    /**
     * Get execution time in milliseconds
     *
     * @return float
     */
    public function getExecutionTimeMs(): float;

    /**
     * Set execution time in milliseconds
     *
     * @param float $time
     * @return $this
     */
    public function setExecutionTimeMs(float $time): self;
}