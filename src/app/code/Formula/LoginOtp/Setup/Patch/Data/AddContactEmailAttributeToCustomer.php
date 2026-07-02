<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Adds a `contact_email` attribute on the customer entity.
 *
 * Since login moved to phone+OTP, accounts no longer carry a real email, and
 * the storefront collects a contact/billing email at checkout. That value can't
 * be written to the primary `email` field (Magento requires password
 * confirmation → 400) nor stored on the address (no email field there), so this
 * gives it a dedicated, editable customer attribute — mirroring the existing
 * `phone` attribute (see AddPhoneAttributeToCustomer). One email per account,
 * survives address edits, and is exposed via the customer REST API's
 * custom_attributes (used_in_forms includes customer_account_edit).
 */
class AddContactEmailAttributeToCustomer implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private CustomerSetupFactory $customerSetupFactory;
    private AttributeSetFactory $attributeSetFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeSetFactory $attributeSetFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $customerEntity = $customerSetup->getEavConfig()->getEntityType(Customer::ENTITY);
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customerSetup->addAttribute(Customer::ENTITY, 'contact_email', [
            'type' => 'varchar',
            'label' => 'Contact Email',
            'input' => 'text',
            'required' => false,
            'visible' => true,
            'user_defined' => true,
            'sort_order' => 111,
            'position' => 111,
            'system' => 0,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true,
            'is_searchable_in_grid' => true,
        ]);

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'contact_email');
        $attribute->addData([
            'attribute_set_id' => $attributeSetId,
            'attribute_group_id' => $attributeGroupId,
            // Exposed on the admin customer form + customer account edit form so
            // it round-trips through the customer REST API's custom_attributes.
            'used_in_forms' => ['adminhtml_customer', 'customer_account_edit'],
        ]);
        $attribute->save();

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [\Formula\LoginOtp\Setup\Patch\Data\AddPhoneAttributeToCustomer::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
