<?php
namespace Formula\Ingredient\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class RemoveIngredientColumn implements SchemaPatchInterface
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
        $connection = $this->moduleDataSetup->getConnection();
        
        // Get the attribute ID for the ingredient attribute
        $attributeId = $connection->fetchOne(
            $connection->select()
                ->from($this->moduleDataSetup->getTable('eav_attribute'), 'attribute_id')
                ->where('attribute_code = ?', 'ingredient')
                ->where('entity_type_id = ?', 4) // Product entity type ID
        );

        if ($attributeId) {
            // Remove data from catalog_product_entity_varchar table
            $connection->delete(
                $this->moduleDataSetup->getTable('catalog_product_entity_varchar'),
                ['attribute_id = ?' => $attributeId]
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            \Formula\Ingredient\Setup\Patch\Data\RemoveIngredientProductAttribute::class
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
