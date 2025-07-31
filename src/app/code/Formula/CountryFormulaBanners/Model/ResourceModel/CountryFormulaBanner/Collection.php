<?php
// app/code/Formula/CountryFormulaBanners/Model/ResourceModel/CountryFormulaBanner/Collection.php
namespace Formula\CountryFormulaBanners\Model\ResourceModel\CountryFormulaBanner;

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
            \Formula\CountryFormulaBanners\Model\CountryFormulaBanner::class,
            \Formula\CountryFormulaBanners\Model\ResourceModel\CountryFormulaBanner::class
        );
    }
}