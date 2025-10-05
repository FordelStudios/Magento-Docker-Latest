<?php
namespace Formula\Wallet\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\Authorization;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class CustomerRepositoryPlugin
{
    /**
     * @var Authorization
     */
    protected $authorization;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CustomerResource
     */
    protected $customerResource;

    /**
     * @param Authorization $authorization
     * @param State $appState
     * @param CustomerResource $customerResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Authorization $authorization,
        State $appState,
        CustomerResource $customerResource,
        LoggerInterface $logger
    ) {
        $this->authorization = $authorization;
        $this->appState = $appState;
        $this->customerResource = $customerResource;
        $this->logger = $logger;
    }

    /**
     * Prevent customers from updating their own wallet balance
     * Always preserve the original wallet balance from database
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $customer
     * @param string|null $passwordHash
     * @return array
     */
    public function beforeSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $customer,
        $passwordHash = null
    ) {
        try {
            // Only apply this restriction in webapi_rest area (customer API calls)
            $areaCode = $this->appState->getAreaCode();
            if ($areaCode !== 'webapi_rest') {
                return [$customer, $passwordHash];
            }

            // Check if user is admin - admins can update wallet balance via their dedicated endpoint
            // The dedicated wallet endpoint uses WalletManagementInterface, not CustomerRepository
            // So this check is for safety in case admin uses customer endpoint directly
            if ($this->authorization->isAllowed('Magento_Customer::manage')) {
                return [$customer, $passwordHash];
            }

            // Customer is trying to update their profile
            // We need to preserve their original wallet balance
            $customerId = $customer->getId();

            if ($customerId) {
                try {
                    // Fetch the original wallet balance directly from database using resource model
                    $connection = $this->customerResource->getConnection();
                    $attributeId = $this->customerResource->getAttribute('wallet_balance')->getId();

                    $select = $connection->select()
                        ->from($this->customerResource->getTable('customer_entity_decimal'), ['value'])
                        ->where('entity_id = ?', $customerId)
                        ->where('attribute_id = ?', $attributeId);

                    $originalWalletBalance = $connection->fetchOne($select);

                    // If original wallet balance exists, force it on the incoming customer object
                    if ($originalWalletBalance !== false) {
                        $customer->setCustomAttribute('wallet_balance', $originalWalletBalance);

                        $this->logger->info('Wallet balance preserved for customer update', [
                            'customer_id' => $customerId,
                            'email' => $customer->getEmail(),
                            'preserved_balance' => $originalWalletBalance
                        ]);
                    } else {
                        // No wallet balance found, set default to 0
                        $customer->setCustomAttribute('wallet_balance', '0.0000');
                    }

                } catch (\Exception $e) {
                    // Error fetching original balance, log and set to 0 to be safe
                    $this->logger->error('Error fetching original wallet balance: ' . $e->getMessage(), [
                        'customer_id' => $customerId
                    ]);
                    $customer->setCustomAttribute('wallet_balance', '0.0000');
                }
            }

        } catch (\Exception $e) {
            // Log error but don't block the customer update
            $this->logger->error('Error in CustomerRepositoryPlugin: ' . $e->getMessage());
        }

        return [$customer, $passwordHash];
    }
}
