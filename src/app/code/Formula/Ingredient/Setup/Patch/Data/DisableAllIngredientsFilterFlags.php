<?php
namespace Formula\Ingredient\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;

/**
 * Clears searchable/filterable/grid-filter flags on the all_ingredients
 * textarea attribute. Those flags caused OpenSearch BadRequest400 errors on
 * every admin grid filter ("Text fields are not optimised for operations
 * that require per-document field data"), which broke saved views across
 * admin pages. Textareas cannot be used for sorting/aggregations.
 */
class DisableAllIngredientsFilterFlags implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributeId = $eavSetup->getAttributeId(Product::ENTITY, 'all_ingredients');
        if (!$attributeId) {
            return $this;
        }

        $flagsToDisable = [
            'is_searchable',
            'is_filterable',
            'is_filterable_in_search',
            'is_filterable_in_grid',
            'used_in_product_listing',
            'is_used_for_promo_rules',
            'is_comparable',
        ];
        foreach ($flagsToDisable as $flag) {
            $eavSetup->updateAttribute(Product::ENTITY, $attributeId, $flag, 0);
        }

        return $this;
    }

    public static function getDependencies()
    {
        return [
            CreateAllIngredientsTextareaAttribute::class,
        ];
    }

    public function getAliases()
    {
        return [];
    }
}
