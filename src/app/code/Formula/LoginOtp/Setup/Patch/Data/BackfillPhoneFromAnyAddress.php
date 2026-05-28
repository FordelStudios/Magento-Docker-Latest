<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Re-runs the phone backfill using ANY address (not just default_billing).
 *
 * Why this exists separately from BackfillPhoneFromBillingAddress:
 * - First backfill (2026-05-28) wrote 0 rows because Formula customers put
 *   phones on shipping addresses, not default_billing.
 * - That caused the "customer 142 incident" — first real phone+OTP login
 *   created a placeholder account instead of finding the user's real one.
 * - This patch is the corrective sweep. Same idempotency rules: skip if
 *   the customer already has a phone EAV value.
 *
 * Per-customer try/catch so one failure doesn't abort the loop and leave
 * the rest unmigrated forever (Magento marks patches APPLIED on completion
 * regardless of internal errors — see prior patch for rationale).
 */
class BackfillPhoneFromAnyAddress implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private LoggerInterface $logger;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    public function apply(): self
    {
        $conn = $this->moduleDataSetup->getConnection();
        $conn->startSetup();

        $phoneAttributeId = (int) $conn->fetchOne(
            "SELECT attribute_id FROM eav_attribute
             WHERE attribute_code = 'phone'
               AND entity_type_id = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'customer')"
        );
        if (!$phoneAttributeId) {
            $this->logger->error('Formula\LoginOtp any-address backfill: phone attribute not found, aborting.');
            $conn->endSetup();
            return $this;
        }

        // Pull every (customer_id, address_id, normalized_phone, is_default_billing,
        // is_default_shipping) tuple. PHP picks one phone per customer with a
        // simple preference: default_billing > default_shipping > smallest addr_id.
        // Easier to reason about than the equivalent SQL window-function dance.
        $raw = $conn->fetchAll(
            "SELECT ce.entity_id AS customer_id,
                    cae.entity_id AS addr_id,
                    RIGHT(REGEXP_REPLACE(cae.telephone, '[^0-9]', ''), 10) AS phone,
                    (cae.entity_id = ce.default_billing) AS is_default_billing,
                    (cae.entity_id = ce.default_shipping) AS is_default_shipping
             FROM customer_entity ce
             JOIN customer_address_entity cae ON cae.parent_id = ce.entity_id
             WHERE cae.telephone IS NOT NULL AND cae.telephone <> ''
               AND CHAR_LENGTH(REGEXP_REPLACE(cae.telephone, '[^0-9]', '')) >= 10"
        );

        // Bucket by customer_id, picking the best candidate per customer.
        $picked = [];  // customer_id => ['phone' => string, 'rank' => int, 'addr_id' => int]
        foreach ($raw as $r) {
            $customerId = (int) $r['customer_id'];
            $rank = $r['is_default_billing'] ? 1 : ($r['is_default_shipping'] ? 2 : 3);
            $addrId = (int) $r['addr_id'];
            $phone = (string) $r['phone'];

            if (!isset($picked[$customerId])
                || $rank < $picked[$customerId]['rank']
                || ($rank === $picked[$customerId]['rank'] && $addrId < $picked[$customerId]['addr_id'])
            ) {
                $picked[$customerId] = [
                    'phone' => $phone,
                    'rank' => $rank,
                    'addr_id' => $addrId,
                ];
            }
        }

        // Reshape to the row format the write loop expects.
        $rows = [];
        foreach ($picked as $customerId => $info) {
            $rows[] = ['customer_id' => $customerId, 'phone' => $info['phone']];
        }

        $written = 0;
        $skipped_existing = 0;
        $failed = [];

        foreach ($rows as $row) {
            try {
                $customerId = (int) $row['customer_id'];
                $phone = (string) $row['phone'];

                // Idempotency
                $existing = $conn->fetchOne(
                    "SELECT value FROM customer_entity_varchar
                     WHERE entity_id = ? AND attribute_id = ?",
                    [$customerId, $phoneAttributeId]
                );
                if ($existing) {
                    $skipped_existing++;
                    continue;
                }

                $conn->insertOnDuplicate(
                    'customer_entity_varchar',
                    [
                        'entity_id' => $customerId,
                        'attribute_id' => $phoneAttributeId,
                        'value' => $phone,
                    ],
                    ['value']
                );
                $written++;
            } catch (\Throwable $e) {
                $failed[] = [
                    'customer_id' => $row['customer_id'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->logger->info('Formula\LoginOtp any-address phone backfill complete', [
            'written' => $written,
            'skipped_already_had_phone' => $skipped_existing,
            'per_row_failures' => $failed,
        ]);

        $conn->endSetup();
        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            \Formula\LoginOtp\Setup\Patch\Data\AddPhoneAttributeToCustomer::class,
            \Formula\LoginOtp\Setup\Patch\Data\BackfillPhoneFromBillingAddress::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
