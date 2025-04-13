<?php
// app/code/Formula/CategoryBanners/Model/ResourceModel/CategoryBanner.php
namespace Formula\CategoryBanners\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CategoryBanner extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('formula_category_banners', 'entity_id');
    }
}