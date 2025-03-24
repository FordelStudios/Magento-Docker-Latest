<?php
namespace Formula\SkinType\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product;

class UpdateSkinTypeToMultiselect implements DataPatchInterface
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

        // Check if attribute exists
        $attributeId = $eavSetup->getAttributeId(Product::ENTITY, 'skintype');
        if ($attributeId) {
            // If exists, we'll delete it and recreate it as multiselect
            $eavSetup->removeAttribute(Product::ENTITY, 'skintype');
        }

        // Add the attribute as multiselect
        $eavSetup->addAttribute(
            Product::ENTITY,
            'skintype',
            [
                'type' => 'varchar', // Changed from int to varchar for multiselect
                'label' => 'SkinTypes',
                'input' => 'multiselect', // Changed from select to multiselect
                'source' => \Formula\SkinType\Model\Product\Attribute\Source\SkinType::class,
                'required' => false,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'user_defined' => false,
                'default' => '',
                'searchable' => true,
                'filterable' => true,
                'comparable' => true,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => '',
                'group' => 'General',
                'sort_order' => 55,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'system' => false,
                'position' => 55,
                'backend' => 'Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend' // Required for multiselect
            ]
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            CreateSkinTypeProductAttribute::class
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