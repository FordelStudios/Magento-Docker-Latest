<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Model\ResourceModel\SpecialOffer;

use Formula\SpecialOffer\Model\ResourceModel\SpecialOffer as ResourceModel;
use Formula\SpecialOffer\Model\SpecialOffer as Model;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
