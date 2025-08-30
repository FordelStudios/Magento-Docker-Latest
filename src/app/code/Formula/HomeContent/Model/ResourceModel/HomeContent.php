<?php
declare(strict_types=1);

namespace Formula\HomeContent\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class HomeContent extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('formula_home_content', 'entity_id');
    }
}