<?php
namespace Formula\OtpValidation\Setup\Patch\Data;

use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Customer\Model\ResourceModel\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddIsVerifiedAttributeToCustomerAddress implements DataPatchInterface
{
    private $moduleDataSetup;
    private $eavSetupFactory;
    private $eavConfig;
    private $attributeResource;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        Attribute $attributeResource
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->attributeResource = $attributeResource;
    }

    public function apply()
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
            AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
            'is_verified',
            [
                'type' => 'int',
                'label' => 'Phone Number Verified',
                'input' => 'boolean',
                'required' => false,
                'default' => 0,
                'sort_order' => 200,
                'visible' => true,
                'system' => false,
                'position' => 200
            ]
        );

        $attribute = $this->eavConfig->getAttribute(
            AddressMetadataInterface::ENTITY_TYPE_ADDRESS,
            'is_verified'
        );

        $attribute->setData('used_in_forms', [
            'customer_address_edit',
            'customer_register_address',
            'adminhtml_customer_address'
        ]);

        $this->attributeResource->save($attribute);

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}