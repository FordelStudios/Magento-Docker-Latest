<?php
namespace Formula\BulkCartDelete\Model\Data;

use Formula\BulkCartDelete\Api\Data\BulkDeleteResponseInterface;
use Magento\Framework\DataObject;

class BulkDeleteResponse extends DataObject implements BulkDeleteResponseInterface
{
    const SUCCESS = 'success';
    const MESSAGE = 'message';
    const TOTAL_REQUESTED = 'total_requested';
    const SUCCESSFULLY_DELETED = 'successfully_deleted';
    const FAILED = 'failed';
    const FAILED_ITEMS = 'failed_items';
    const EXECUTION_TIME_MS = 'execution_time_ms';

    /**
     * Get success status
     *
     * @return bool
     */
    public function getSuccess(): bool
    {
        return (bool)$this->getData(self::SUCCESS);
    }

    /**
     * Set success status
     *
     * @param bool $success
     * @return $this
     */
    public function setSuccess(bool $success): BulkDeleteResponseInterface
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return (string)$this->getData(self::MESSAGE);
    }

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): BulkDeleteResponseInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * Get total requested items
     *
     * @return int
     */
    public function getTotalRequested(): int
    {
        return (int)$this->getData(self::TOTAL_REQUESTED);
    }

    /**
     * Set total requested items
     *
     * @param int $total
     * @return $this
     */
    public function setTotalRequested(int $total): BulkDeleteResponseInterface
    {
        return $this->setData(self::TOTAL_REQUESTED, $total);
    }

    /**
     * Get successfully deleted count
     *
     * @return int
     */
    public function getSuccessfullyDeleted(): int
    {
        return (int)$this->getData(self::SUCCESSFULLY_DELETED);
    }

    /**
     * Set successfully deleted count
     *
     * @param int $count
     * @return $this
     */
    public function setSuccessfullyDeleted(int $count): BulkDeleteResponseInterface
    {
        return $this->setData(self::SUCCESSFULLY_DELETED, $count);
    }

    /**
     * Get failed count
     *
     * @return int
     */
    public function getFailed(): int
    {
        return (int)$this->getData(self::FAILED);
    }

    /**
     * Set failed count
     *
     * @param int $count
     * @return $this
     */
    public function setFailed(int $count): BulkDeleteResponseInterface
    {
        return $this->setData(self::FAILED, $count);
    }

    /**
     * Get failed items details
     *
     * @return \Formula\BulkCartDelete\Api\Data\FailedItemInterface[]
     */
    public function getFailedItems(): array
    {
        return (array)$this->getData(self::FAILED_ITEMS);
    }

    /**
     * Set failed items details
     *
     * @param \Formula\BulkCartDelete\Api\Data\FailedItemInterface[] $failedItems
     * @return $this
     */
    public function setFailedItems(array $failedItems): BulkDeleteResponseInterface
    {
        return $this->setData(self::FAILED_ITEMS, $failedItems);
    }

    /**
     * Get execution time in milliseconds
     *
     * @return float
     */
    public function getExecutionTimeMs(): float
    {
        return (float)$this->getData(self::EXECUTION_TIME_MS);
    }

    /**
     * Set execution time in milliseconds
     *
     * @param float $time
     * @return $this
     */
    public function setExecutionTimeMs(float $time): BulkDeleteResponseInterface
    {
        return $this->setData(self::EXECUTION_TIME_MS, $time);
    }
}
