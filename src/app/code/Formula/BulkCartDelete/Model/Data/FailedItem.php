<?php declare(strict_types=1);


// File: Model/Data/FailedItem.php

namespace Formula\BulkCartDelete\Model\Data;

use Formula\BulkCartDelete\Api\Data\FailedItemInterface;
use Magento\Framework\DataObject;

class FailedItem extends DataObject implements FailedItemInterface
{
    const ITEM_ID = 'item_id';
    const ERROR = 'error';

    /**
     * Get item ID
     *
     * @return int
     */
    public function getItemId(): int
    {
        return (int)$this->getData(self::ITEM_ID);
    }

    /**
     * Set item ID
     *
     * @param int $itemId
     * @return $this
     */
    public function setItemId(int $itemId): FailedItemInterface
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getError(): string
    {
        return (string)$this->getData(self::ERROR);
    }

    /**
     * Set error message
     *
     * @param string $error
     * @return $this
     */
    public function setError(string $error): FailedItemInterface
    {
        return $this->setData(self::ERROR, $error);
    }
}