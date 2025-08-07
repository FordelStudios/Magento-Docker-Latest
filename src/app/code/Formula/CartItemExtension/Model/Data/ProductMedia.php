<?php
namespace Formula\CartItemExtension\Model\Data;

use Formula\CartItemExtension\Api\Data\ProductMediaInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class ProductMedia extends AbstractSimpleObject implements ProductMediaInterface
{
    private const KEY_ID = 'id';
    private const KEY_FILE = 'file';

    public function getId()
    {
        return $this->_get(self::KEY_ID);
    }

    public function setId($id)
    {
        return $this->setData(self::KEY_ID, (int)$id);
    }

    public function getFile()
    {
        return $this->_get(self::KEY_FILE);
    }

    public function setFile($file)
    {
        return $this->setData(self::KEY_FILE, (string)$file);
    }
}

