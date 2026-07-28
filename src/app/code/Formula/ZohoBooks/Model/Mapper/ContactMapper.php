<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Model\Mapper;

use Formula\ZohoBooks\Model\Gst\StateCodeMapper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Pure mapper: builds a Zoho Books Contact payload (POST /contacts) from a Magento order.
 *
 * Field names/shape follow the Zoho Books API v3 Contacts documentation as best understood
 * today. This class only builds the payload - the API client, upsert logic, and dedup
 * against an existing Zoho contact are later tasks.
 *
 * @todo Reconcile field names/shape against Zoho Books API v3 + org config when P2/P3 lands
 *       (this has NOT been verified against a live Zoho org).
 */
class ContactMapper
{
    /**
     * @todo B2B GSTIN capture (registered-business customers, gst_treatment=business_gst)
     *       is out of scope for this task - every order is mapped as a B2C consumer contact.
     */
    public const GST_TREATMENT_CONSUMER = 'consumer';

    public function __construct(
        private readonly StateCodeMapper $stateCodeMapper
    ) {
    }

    /**
     * @throws LocalizedException when the order has no billing address, or its region
     *                            cannot be resolved to a GST state (see StateCodeMapper)
     */
    public function build(OrderInterface $order): array
    {
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress === null) {
            throw new LocalizedException(__(
                'Order "%1" has no billing address; cannot build a Zoho contact without one.',
                (string) $order->getIncrementId()
            ));
        }

        $firstName = (string) $billingAddress->getFirstname();
        $lastName = (string) $billingAddress->getLastname();
        $contactName = trim($firstName . ' ' . $lastName);
        if ($contactName === '') {
            $contactName = (string) $order->getCustomerEmail();
        }

        $placeOfContact = $this->stateCodeMapper->resolve(
            $billingAddress->getRegionCode(),
            $billingAddress->getRegion()
        );

        return [
            'contact_name' => $contactName,
            'company_name' => $this->nullableString($billingAddress->getCompany()),
            'contact_persons' => [
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => (string) $order->getCustomerEmail(),
                    'phone' => (string) $billingAddress->getTelephone(),
                ],
            ],
            'billing_address' => [
                'address' => $this->formatStreet($billingAddress->getStreet()),
                'city' => (string) $billingAddress->getCity(),
                'state' => (string) $billingAddress->getRegion(),
                'zip' => (string) $billingAddress->getPostcode(),
                'country' => (string) $billingAddress->getCountryId(),
            ],
            'gst_treatment' => self::GST_TREATMENT_CONSUMER,
            'place_of_contact' => $placeOfContact,
        ];
    }

    private function nullableString(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param string[]|null $street
     */
    private function formatStreet(?array $street): string
    {
        if ($street === null) {
            return '';
        }

        $lines = array_filter(array_map('trim', $street), static fn (string $line): bool => $line !== '');

        return implode(', ', $lines);
    }
}
