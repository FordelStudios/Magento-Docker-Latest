<?php
namespace Formula\FaceConcern\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateSourceModelReferences implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        
        // Update source_model in eav_attribute table
        $connection->update(
            $this->moduleDataSetup->getTable('eav_attribute'),
            ['source_model' => 'Formula\FaceConcern\Model\Product\Attribute\Source\FaceConcern'],
            ['source_model = ?' => 'Formula\SkinConcern\Model\Product\Attribute\Source\SkinConcern']
        );
        
        // Update any other references in core_config_data if they exist
        $connection->update(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['value' => new \Zend_Db_Expr('REPLACE(value, "Formula\\\\SkinConcern", "Formula\\\\FaceConcern")')],
            ['value LIKE ?' => '%Formula\\\\SkinConcern%']
        );

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            CreateFaceConcernProductAttribute::class,
            UpdateFaceConcernToMultiselect::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}