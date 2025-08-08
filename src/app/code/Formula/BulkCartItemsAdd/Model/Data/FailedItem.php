<?php
declare(strict_types=1);

namespace Formula\BulkCartItemsAdd\Model\Data;

use Formula\BulkCartItemsAdd\Api\Data\FailedItemInterface;
use Magento\Framework\DataObject;

class FailedItem extends DataObject implements FailedItemInterface
{
    public const SKU = 'sku';
    public const ERROR = 'error';

    public function getSku(): string
    {
        return (string)$this->getData(self::SKU);
    }

    public function setSku(string $sku): FailedItemInterface
    {
        return $this->setData(self::SKU, $sku);
    }

    public function getError(): string
    {
        return (string)$this->getData(self::ERROR);
    }

    public function setError(string $error): FailedItemInterface
    {
        return $this->setData(self::ERROR, $error);
    }
}
