<?php
declare(strict_types=1);

namespace Formula\Review\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddReviewAggregationAttributes implements DataPatchInterface
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
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // Add rating_summary attribute (decimal, 1-5 scale)
        $eavSetup->addAttribute(
            Product::ENTITY,
            'rating_summary',
            [
                'type'                    => 'decimal',
                'label'                   => 'Rating Summary',
                'input'                   => 'text',
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
                'sort_order'              => 60,
                'is_used_in_grid'         => true,
                'is_visible_in_grid'      => true,
                'is_filterable_in_grid'   => true,
                'used_for_sort_by'        => true,
                'note'                    => 'Average rating (1-5 scale). Automatically synced from reviews.',
            ]
        );

        // Add reviews_count attribute (integer)
        $eavSetup->addAttribute(
            Product::ENTITY,
            'reviews_count',
            [
                'type'                    => 'int',
                'label'                   => 'Reviews Count',
                'input'                   => 'text',
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
                'sort_order'              => 61,
                'is_used_in_grid'         => true,
                'is_visible_in_grid'      => true,
                'is_filterable_in_grid'   => true,
                'used_for_sort_by'        => true,
                'note'                    => 'Total number of reviews. Automatically synced from reviews.',
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
