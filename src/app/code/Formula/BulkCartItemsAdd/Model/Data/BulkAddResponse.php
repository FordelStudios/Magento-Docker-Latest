<?php
declare(strict_types=1);

namespace Formula\BulkCartItemsAdd\Model\Data;

use Formula\BulkCartItemsAdd\Api\Data\BulkAddResponseInterface;
use Magento\Framework\DataObject;

class BulkAddResponse extends DataObject implements BulkAddResponseInterface
{
    public const SUCCESS = 'success';
    public const MESSAGE = 'message';
    public const TOTAL_REQUESTED = 'total_requested';
    public const SUCCESSFULLY_ADDED = 'successfully_added';
    public const FAILED = 'failed';
    public const FAILED_ITEMS = 'failed_items';
    public const EXECUTION_TIME_MS = 'execution_time_ms';

    public function getSuccess(): bool
    {
        return (bool)$this->getData(self::SUCCESS);
    }

    public function setSuccess(bool $success): BulkAddResponseInterface
    {
        return $this->setData(self::SUCCESS, $success);
    }

    public function getMessage(): string
    {
        return (string)$this->getData(self::MESSAGE);
    }

    public function setMessage(string $message): BulkAddResponseInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }

    public function getTotalRequested(): int
    {
        return (int)$this->getData(self::TOTAL_REQUESTED);
    }

    public function setTotalRequested(int $total): BulkAddResponseInterface
    {
        return $this->setData(self::TOTAL_REQUESTED, $total);
    }

    public function getSuccessfullyAdded(): int
    {
        return (int)$this->getData(self::SUCCESSFULLY_ADDED);
    }

    public function setSuccessfullyAdded(int $count): BulkAddResponseInterface
    {
        return $this->setData(self::SUCCESSFULLY_ADDED, $count);
    }

    public function getFailed(): int
    {
        return (int)$this->getData(self::FAILED);
    }

    public function setFailed(int $count): BulkAddResponseInterface
    {
        return $this->setData(self::FAILED, $count);
    }

    public function getFailedItems(): array
    {
        return (array)$this->getData(self::FAILED_ITEMS);
    }

    public function setFailedItems(array $failedItems): BulkAddResponseInterface
    {
        return $this->setData(self::FAILED_ITEMS, $failedItems);
    }

    public function getExecutionTimeMs(): float
    {
        return (float)$this->getData(self::EXECUTION_TIME_MS);
    }

    public function setExecutionTimeMs(float $ms): BulkAddResponseInterface
    {
        return $this->setData(self::EXECUTION_TIME_MS, $ms);
    }
}
