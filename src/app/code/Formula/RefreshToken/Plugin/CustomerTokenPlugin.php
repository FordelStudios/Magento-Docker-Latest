<?php
namespace Formula\RefreshToken\Plugin;

use Formula\RefreshToken\Model\RefreshTokenFactory;
use Formula\RefreshToken\Model\ResourceModel\RefreshToken as RefreshTokenResource;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Math\Random;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\State\UserLockedException;

class CustomerTokenPlugin
{
    protected $tokenFactory;
    protected $tokenResource;
    protected $dateTime;
    protected $random;
    protected $customerRepository;
    protected $accountManagement;

    public function __construct(
        RefreshTokenFactory $tokenFactory,
        RefreshTokenResource $tokenResource,
        DateTime $dateTime,
        Random $random,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->tokenResource = $tokenResource;
        $this->dateTime = $dateTime;
        $this->random = $random;
        $this->customerRepository = $customerRepository;
        $this->accountManagement = $accountManagement;
    }

    public function aroundCreateCustomerAccessToken(
        $subject,
        callable $proceed,
        $username,
        $password
    ) {
        // First check if customer exists
        try {
            $customer = $this->customerRepository->get($username);
        } catch (NoSuchEntityException $e) {
            throw new AuthenticationException(
                __('No account found with this email address.')
            );
        }

        // Check if customer account is active
        $customerData = $customer->getExtensionAttributes();
        if ($customer->getCustomAttribute('is_active')) {
            $isActive = $customer->getCustomAttribute('is_active')->getValue();
            if (!$isActive) {
                throw new AuthenticationException(
                    __('Your account has been disabled. Please contact support.')
                );
            }
        }

        // Check confirmation status (email verification)
        try {
            $confirmationStatus = $this->accountManagement->getConfirmationStatus($customer->getId());
            if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
                throw new AuthenticationException(
                    __('Please verify your email address before logging in.')
                );
            }
        } catch (\Exception $e) {
            // If we can't check confirmation status, continue with authentication
        }

        // Try to authenticate
        try {
            $result = $proceed($username, $password);
        } catch (UserLockedException $e) {
            throw new AuthenticationException(
                __('Your account is locked due to too many failed login attempts. Please try again later.')
            );
        } catch (InvalidEmailOrPasswordException $e) {
            throw new AuthenticationException(
                __('Invalid password. Please check your password and try again.')
            );
        } catch (AuthenticationException $e) {
            // Check if it's a password issue (customer exists but auth failed)
            throw new AuthenticationException(
                __('Invalid password. Please check your password and try again.')
            );
        }

        if (!$result) {
            return $result;
        }

        $customerId = $customer->getId();

        // Generate refresh token
        $refreshTokenValue = $this->random->getUniqueHash();
        $expiresAt = $this->dateTime->gmtDate('Y-m-d H:i:s', strtotime('+30 days'));

        $refreshToken = $this->tokenFactory->create();
        $refreshToken->setCustomerId($customerId);
        $refreshToken->setToken($refreshTokenValue);
        $refreshToken->setExpiresAt($expiresAt);

        $this->tokenResource->save($refreshToken);

        // Return structured response
        return [
            'access_token'  => $result,
            'refresh_token' => $refreshTokenValue
        ];
    }
}
