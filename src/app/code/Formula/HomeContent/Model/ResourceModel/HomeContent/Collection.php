<?php
declare(strict_types=1);

namespace Formula\HomeContent\Model\ResourceModel\HomeContent;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Formula\HomeContent\Model\HomeContent;
use Formula\HomeContent\Model\ResourceModel\HomeContent as HomeContentResourceModel;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(HomeContent::class, HomeContentResourceModel::class);
    }
}