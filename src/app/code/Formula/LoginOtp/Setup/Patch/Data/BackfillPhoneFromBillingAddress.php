<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Backfills the new customer `phone` attribute from default-billing-address
 * telephone for existing customers.
 *
 * Normalization: strip non-digits, take last 10 chars (matches the audit query
 * used in [[formula_data_baseline]]).
 *
 * Collision handling: if multiple customers normalize to the same phone, NONE
 * of them get backfilled — they're logged for manual resolution. The audit
 * (2026-05-28) identified 3 collision phones across 8 customer accounts; those
 * customers must be resolved manually before phone+OTP launch.
 *
 * Idempotent: only writes if the phone attribute is currently NULL/empty for
 * the customer.
 */
class BackfillPhoneFromBillingAddress implements DataPatchInterface
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

        // Find the phone attribute id once.
        $phoneAttributeId = (int) $conn->fetchOne(
            "SELECT attribute_id FROM eav_attribute
             WHERE attribute_code = 'phone'
               AND entity_type_id = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'customer')"
        );
        if (!$phoneAttributeId) {
            $this->logger->error('Formula\LoginOtp backfill: phone attribute not found, aborting.');
            $conn->endSetup();
            return $this;
        }

        // Pull normalized phone + customer_id pairs from default billing addresses.
        // We use default_billing.address.telephone because that's the customer's primary
        // address; shipping addresses can be one-off (gift orders, work address).
        $rows = $conn->fetchAll(
            "SELECT ce.entity_id AS customer_id,
                    RIGHT(REGEXP_REPLACE(cae.telephone, '[^0-9]', ''), 10) AS phone
             FROM customer_entity ce
             JOIN customer_address_entity cae ON cae.entity_id = ce.default_billing
             WHERE cae.telephone IS NOT NULL AND cae.telephone <> ''
               AND CHAR_LENGTH(REGEXP_REPLACE(cae.telephone, '[^0-9]', '')) >= 10"
        );

        // Bucket by normalized phone to detect collisions.
        $byPhone = [];
        foreach ($rows as $r) {
            $byPhone[$r['phone']][] = (int) $r['customer_id'];
        }

        $written = 0;
        $skipped_collision = [];
        $skipped_existing = 0;
        $failed = [];

        foreach ($byPhone as $phone => $customerIds) {
            if (count($customerIds) > 1) {
                $skipped_collision[$phone] = $customerIds;
                continue;
            }
            $customerId = $customerIds[0];

            // Per-row try/catch — one failure must NOT abort the loop. Magento
            // marks a patch APPLIED on success and won't retry, so an aborting
            // exception here would leave the remaining customers unmigrated
            // forever (requires a manual fixup patch). Log + continue instead.
            try {
                // Skip if customer already has a phone value (idempotency).
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
                    'customer_id' => $customerId,
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->logger->info('Formula\LoginOtp phone backfill complete', [
            'written' => $written,
            'skipped_already_had_phone' => $skipped_existing,
            'collisions_needing_manual_resolution' => $skipped_collision,
            'per_row_failures' => $failed,
        ]);

        $conn->endSetup();
        return $this;
    }

    public static function getDependencies(): array
    {
        // Must run AFTER the phone attribute is created.
        return [AddPhoneAttributeToCustomer::class];
    }

    public function getAliases(): array
    {
        return [];
    }
}
