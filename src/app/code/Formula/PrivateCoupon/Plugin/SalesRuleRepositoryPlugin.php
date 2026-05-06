<?php
namespace Formula\PrivateCoupon\Plugin;

use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Api\Data\RuleExtensionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class SalesRuleRepositoryPlugin
{
    private RuleExtensionFactory $extensionFactory;
    private ResourceConnection $resource;
    private LoggerInterface $logger;

    public function __construct(
        RuleExtensionFactory $extensionFactory,
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    public function afterGetById(RuleRepositoryInterface $subject, RuleInterface $rule): RuleInterface
    {
        try {
            $flags = $this->fetchHiddenFlags([(int) $rule->getRuleId()]);
            $this->attachExtensionAttribute($rule, $flags);
        } catch (\Throwable $e) {
            $this->logger->warning('PrivateCoupon afterGetById failed: ' . $e->getMessage());
        }
        return $rule;
    }

    /**
     * @param RuleRepositoryInterface $subject
     * @param mixed $searchResults Magento returns either RuleSearchResultInterface or
     *                             a generic SearchResults depending on caller — keep loose.
     * @return mixed
     */
    public function afterGetList(RuleRepositoryInterface $subject, $searchResults)
    {
        try {
            $items = $searchResults->getItems();
            if (!empty($items)) {
                $ruleIds = [];
                foreach ($items as $rule) {
                    $ruleIds[] = (int) $rule->getRuleId();
                }
                $flags = $this->fetchHiddenFlags($ruleIds);
                foreach ($items as $rule) {
                    $this->attachExtensionAttribute($rule, $flags);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('PrivateCoupon afterGetList failed: ' . $e->getMessage());
        }
        return $searchResults;
    }

    /**
     * Batch-fetch is_storefront_hidden values keyed by rule_id.
     */
    private function fetchHiddenFlags(array $ruleIds): array
    {
        if (empty($ruleIds)) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('salesrule');
        $select = $connection->select()
            ->from($table, ['rule_id', 'is_storefront_hidden'])
            ->where('rule_id IN (?)', $ruleIds);
        return $connection->fetchPairs($select);
    }

    private function attachExtensionAttribute(RuleInterface $rule, array $flags): void
    {
        $extensionAttributes = $rule->getExtensionAttributes() ?? $this->extensionFactory->create();
        $value = (int) ($flags[(int) $rule->getRuleId()] ?? 0);
        $extensionAttributes->setIsStorefrontHidden($value);
        $rule->setExtensionAttributes($extensionAttributes);
    }
}
