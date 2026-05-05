<?php
namespace Formula\PrivateCoupon\Plugin;

use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\Data\RuleSearchResultInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\RuleExtensionFactory;

class SalesRuleRepositoryPlugin
{
    private RuleExtensionFactory $extensionFactory;

    public function __construct(RuleExtensionFactory $extensionFactory)
    {
        $this->extensionFactory = $extensionFactory;
    }

    public function afterGetById(RuleRepositoryInterface $subject, RuleInterface $rule): RuleInterface
    {
        $this->attachExtensionAttribute($rule);
        return $rule;
    }

    public function afterGetList(RuleRepositoryInterface $subject, RuleSearchResultInterface $searchResults): RuleSearchResultInterface
    {
        foreach ($searchResults->getItems() as $rule) {
            $this->attachExtensionAttribute($rule);
        }
        return $searchResults;
    }

    private function attachExtensionAttribute(RuleInterface $rule): void
    {
        $extensionAttributes = $rule->getExtensionAttributes() ?? $this->extensionFactory->create();
        $extensionAttributes->setIsStorefrontHidden((int) $rule->getData('is_storefront_hidden'));
        $rule->setExtensionAttributes($extensionAttributes);
    }
}
