<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Service;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolves "this phone" → "this customer", creating one if needed.
 *
 * Lookup order:
 *   1. customer with `phone` attribute == phone   (canonical, post-migration)
 *   2. customer whose default billing address normalizes to phone
 *      (covers pre-migration accounts before backfill patch has run, and any
 *      future customers who added a phone via address only)
 *
 * Create path:
 *   - email = "<phone>@<placeholder_email_domain>" (default `formula.placeholder`)
 *   - phone = normalized 10-digit
 *   - firstname/lastname = "" (collected later at checkout / profile)
 *   - confirmation = null (account.confirm config is being turned off anyway)
 */
class CustomerFinder
{
    public const XML_PATH_PLACEHOLDER_DOMAIN = 'formula_login_otp/general/placeholder_email_domain';

    private CustomerRepositoryInterface $customerRepository;
    private CustomerInterfaceFactory $customerFactory;
    private CustomerCollectionFactory $customerCollectionFactory;
    private ResourceConnection $resource;
    private StoreManagerInterface $storeManager;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @return array{customer: CustomerInterface, is_new: bool}
     */
    public function findOrCreateByPhone(string $normalizedPhone): array
    {
        $existing = $this->findByPhone($normalizedPhone);
        if ($existing) {
            return ['customer' => $existing, 'is_new' => false];
        }

        $created = $this->createPlaceholderCustomer($normalizedPhone);
        return ['customer' => $created, 'is_new' => true];
    }

    /**
     * Lookup-only: returns null if no matching account exists. Used by the
     * email-recovery flow so we can return "no account with that email" cleanly.
     */
    public function findByPhone(string $normalizedPhone): ?CustomerInterface
    {
        // Step 1: by canonical `phone` attribute
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToFilter('phone', $normalizedPhone)
                   ->setPageSize(1);
        $row = $collection->getFirstItem();
        if ($row && $row->getId()) {
            return $this->customerRepository->getById((int) $row->getId());
        }

        // Step 2: by ANY address.telephone (last 10 digits).
        //
        // Why "any address" and not just default_billing:
        // - audit (2026-05-28 in [[formula-data-baseline]]) showed real customers
        //   put phone on addresses where is_default_billing is NULL
        // - first deploy of this code looked only at default_billing and missed
        //   ~all of them, creating placeholder accounts for users who already
        //   had real accounts (the customer 142 incident)
        //
        // Preference order, in case multiple addresses for the same customer
        // (or across customers) share the phone:
        //   1. default_billing match (strongest signal that this is "the" account)
        //   2. default_shipping match
        //   3. lowest customer entity_id (deterministic; tends to match the older account)
        $conn = $this->resource->getConnection();
        $customerId = $conn->fetchOne(
            "SELECT ce.entity_id
             FROM customer_entity ce
             JOIN customer_address_entity cae ON cae.parent_id = ce.entity_id
             WHERE RIGHT(REGEXP_REPLACE(cae.telephone, '[^0-9]', ''), 10) = ?
             ORDER BY
               (cae.entity_id = ce.default_billing) DESC,
               (cae.entity_id = ce.default_shipping) DESC,
               ce.entity_id ASC
             LIMIT 1",
            [$normalizedPhone]
        );
        if ($customerId) {
            return $this->customerRepository->getById((int) $customerId);
        }

        return null;
    }

    public function findByEmail(string $email): ?CustomerInterface
    {
        try {
            return $this->customerRepository->get($email);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Sets the `phone` attribute on an existing customer (used by recovery flow
     * after a legacy customer verifies their email and provides a phone).
     */
    public function setPhoneOnCustomer(CustomerInterface $customer, string $normalizedPhone): CustomerInterface
    {
        // Check uniqueness first — another account may have grabbed this phone
        // between the email-OTP verify and the add-phone call.
        $other = $this->findByPhone($normalizedPhone);
        if ($other && (int) $other->getId() !== (int) $customer->getId()) {
            throw new LocalizedException(
                __('That phone number is already linked to another account.')
            );
        }

        $customer->setCustomAttribute('phone', $normalizedPhone);
        return $this->customerRepository->save($customer);
    }

    private function createPlaceholderCustomer(string $normalizedPhone): CustomerInterface
    {
        $domain = (string) $this->scopeConfig->getValue(
            self::XML_PATH_PLACEHOLDER_DOMAIN,
            ScopeInterface::SCOPE_STORE
        ) ?: 'formula.placeholder';

        $email = $normalizedPhone . '@' . $domain;

        /** @var CustomerInterface $customer */
        $customer = $this->customerFactory->create();
        $customer->setEmail($email);
        $customer->setFirstname('Customer');  // Magento requires non-empty
        $customer->setLastname('-');
        $customer->setWebsiteId((int) $this->storeManager->getWebsite()->getId());
        $customer->setStoreId((int) $this->storeManager->getStore()->getId());
        $customer->setCustomAttribute('phone', $normalizedPhone);
        // Skip the confirmation-email flow — even if account.confirm is on
        // (it isn't anymore), a phone-OTP signup has already verified identity.
        $customer->setConfirmation(null);

        try {
            return $this->customerRepository->save($customer);
        } catch (\Exception $e) {
            $this->logger->error('Formula\LoginOtp: createPlaceholderCustomer failed', [
                'phone' => $normalizedPhone,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
