<?php
namespace Formula\CategoryBentoBanners\Model\ResourceModel\CategoryBentoBanner;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Formula\CategoryBentoBanners\Model\CategoryBentoBanner::class,
            \Formula\CategoryBentoBanners\Model\ResourceModel\CategoryBentoBanner::class
        );
    }
}