<?php

namespace Formula\ProductRoutine\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class AddProductRoutineAttributes implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Remove existing attributes first to ensure clean creation
        if ($eavSetup->getAttributeId(Product::ENTITY, 'routine_type')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'routine_type');
        }
        if ($eavSetup->getAttributeId(Product::ENTITY, 'routine_timing')) {
            $eavSetup->removeAttribute(Product::ENTITY, 'routine_timing');
        }

        // Add routineType attribute (multiselect)
        $eavSetup->addAttribute(
            Product::ENTITY,
            'routine_type',
            [
                'type' => 'varchar',
                'label' => 'Routine Type',
                'input' => 'multiselect',
                'source' => \Formula\ProductRoutine\Model\Config\Source\RoutineType::class,
                'backend' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
                'required' => false,
                'sort_order' => 100,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'General',
                'used_in_product_listing' => true,
                'visible_on_front' => true,
                'used_for_sort_by' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_in_advanced_search' => false,
                'used_for_promo_rules' => false,
                'html_allowed_on_front' => false,
                'apply_to' => '',
                'is_wysiwyg_enabled' => false,
                'is_html_allowed_on_front' => false,
                'default' => null
            ]
        );

        // Add routineTiming attribute (select) - CRITICAL: Use 'int' type with proper source model
        $eavSetup->addAttribute(
            Product::ENTITY,
            'routine_timing',
            [
                'type' => 'int', // Changed to int for proper option handling
                'label' => 'Routine Timing',
                'input' => 'select',
                'source' => \Formula\ProductRoutine\Model\Config\Source\RoutineTiming::class,
                'required' => false,
                'sort_order' => 101,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'General',
                'used_in_product_listing' => true,
                'visible_on_front' => true,
                'used_for_sort_by' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_in_advanced_search' => false,
                'used_for_promo_rules' => false,
                'html_allowed_on_front' => false,
                'apply_to' => '',
                'is_wysiwyg_enabled' => false,
                'is_html_allowed_on_front' => false,
                'default' => null,
                'option' => [
                    'values' => [
                        0 => 'Day',
                        1 => 'Night', 
                        2 => 'Anytime'
                    ]
                ]
            ]
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->removeAttribute(Product::ENTITY, 'routine_type');
        $eavSetup->removeAttribute(Product::ENTITY, 'routine_timing');

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}