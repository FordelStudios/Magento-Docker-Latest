<?php
namespace Formula\ProductFlags\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddProductFlagsAttributes implements DataPatchInterface
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
     * Constructor
     *
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
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Add Giftset Attribute
        $eavSetup->addAttribute(
            Product::ENTITY,
            'giftset',
            [
                'type'                    => 'int',
                'label'                   => 'Gift Set',
                'input'                   => 'boolean',
                'source'                  => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required'                => false,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'user_defined'            => false,
                'default'                 => '0',
                'searchable'              => false,
                'filterable'              => true,
                'comparable'              => false,
                'visible_on_front'        => true,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => '',
                'group'                   => 'General',
                'sort_order'              => 100,
                'is_used_in_grid'         => true,
                'is_visible_in_grid'      => true,
                'is_filterable_in_grid'   => true,
            ]
        );

        // Add New Arrival Attribute
        $eavSetup->addAttribute(
            Product::ENTITY,
            'new_arrival',
            [
                'type'                    => 'int',
                'label'                   => 'New Arrival',
                'input'                   => 'boolean',
                'source'                  => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required'                => false,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'user_defined'            => false,
                'default'                 => '0',
                'searchable'              => false,
                'filterable'              => true,
                'comparable'              => false,
                'visible_on_front'        => true,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => '',
                'group'                   => 'General',
                'sort_order'              => 101,
                'is_used_in_grid'         => true,
                'is_visible_in_grid'      => true,
                'is_filterable_in_grid'   => true,
            ]
        );

        // Add Trending Attribute
        $eavSetup->addAttribute(
            Product::ENTITY,
            'trending',
            [
                'type'                    => 'int',
                'label'                   => 'Trending',
                'input'                   => 'boolean',
                'source'                  => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required'                => false,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'user_defined'            => false,
                'default'                 => '0',
                'searchable'              => false,
                'filterable'              => true,
                'comparable'              => false,
                'visible_on_front'        => true,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => '',
                'group'                   => 'General',
                'sort_order'              => 102,
                'is_used_in_grid'         => true,
                'is_visible_in_grid'      => true,
                'is_filterable_in_grid'   => true,
            ]
        );

        // Add Popular Attribute
        $eavSetup->addAttribute(
            Product::ENTITY,
            'popular',
            [
                'type'                    => 'int',
                'label'                   => 'Popular',
                'input'                   => 'boolean',
                'source'                  => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required'                => false,
                'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'user_defined'            => false,
                'default'                 => '0',
                'searchable'              => false,
                'filterable'              => true,
                'comparable'              => false,
                'visible_on_front'        => true,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => '',
                'group'                   => 'General',
                'sort_order'              => 103,
                'is_used_in_grid'         => true,
                'is_visible_in_grid'      => true,
                'is_filterable_in_grid'   => true,
            ]
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
