<?php
// app/code/Formula/CategoryBanners/Model/ResourceModel/CategoryBanner/Collection.php
namespace Formula\CategoryBanners\Model\ResourceModel\CategoryBanner;

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
            \Formula\CategoryBanners\Model\CategoryBanner::class,
            \Formula\CategoryBanners\Model\ResourceModel\CategoryBanner::class
        );
    }
}