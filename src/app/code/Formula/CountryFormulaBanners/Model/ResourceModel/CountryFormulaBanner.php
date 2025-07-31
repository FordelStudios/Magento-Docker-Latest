<?php
// app/code/Formula/CountryFormulaBanners/Model/ResourceModel/CountryFormulaBanner.php
namespace Formula\CountryFormulaBanners\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CountryFormulaBanner extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('formula_country_formula_banners', 'entity_id');
    }
}