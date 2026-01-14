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

    /**
     * Check if customer email is verified
     *
     * @param int $customerId
     * @return bool True if verification is required (not verified), false if verified or check not needed
     */
    private function isEmailVerificationRequired($customerId)
    {
        try {
            $confirmationStatus = $this->accountManagement->getConfirmationStatus($customerId);
            return $confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED;
        } catch (\Exception $e) {
            // If we can't determine status, assume verified (don't block login)
            return false;
        }
    }

    public function aroundCreateCustomerAccessToken(
        $subject,
        callable $proceed,
        $username,
        $password
    ) {
        $customer = null;

        // First check if customer exists
        try {
            $customer = $this->customerRepository->get($username);
        } catch (NoSuchEntityException $e) {
            throw new AuthenticationException(
                __('No account found with this email address.')
            );
        } catch (\Exception $e) {
            throw new AuthenticationException(
                __('Unable to process login. Please try again.')
            );
        }

        $customerId = $customer->getId();

        // Check if customer account is active
        try {
            if ($customer->getCustomAttribute('is_active')) {
                $isActive = $customer->getCustomAttribute('is_active')->getValue();
                if (!$isActive) {
                    throw new AuthenticationException(
                        __('Your account has been disabled. Please contact support.')
                    );
                }
            }
        } catch (AuthenticationException $e) {
            throw $e; // Re-throw our own exception
        } catch (\Exception $e) {
            // If we can't check is_active, continue (attribute might not exist)
        }

        // Check confirmation status (email verification) BEFORE attempting auth
        if ($this->isEmailVerificationRequired($customerId)) {
            throw new AuthenticationException(
                __('Please verify your email address before logging in.')
            );
        }

        // Try to authenticate
        try {
            $result = $proceed($username, $password);
        } catch (UserLockedException $e) {
            throw new AuthenticationException(
                __('Your account is locked due to too many failed login attempts. Please try again later.')
            );
        } catch (InvalidEmailOrPasswordException $e) {
            // Re-check email verification in case Magento core rejected for that reason
            if ($this->isEmailVerificationRequired($customerId)) {
                throw new AuthenticationException(
                    __('Please verify your email address before logging in.')
                );
            }
            throw new AuthenticationException(
                __('Invalid password. Please check your password and try again.')
            );
        } catch (AuthenticationException $e) {
            // Re-check email verification - Magento might throw generic auth error for unverified
            if ($this->isEmailVerificationRequired($customerId)) {
                throw new AuthenticationException(
                    __('Please verify your email address before logging in.')
                );
            }
            throw new AuthenticationException(
                __('Invalid password. Please check your password and try again.')
            );
        } catch (\Exception $e) {
            // Catch-all for any unexpected errors
            // Still check verification status as it might be the underlying cause
            if ($this->isEmailVerificationRequired($customerId)) {
                throw new AuthenticationException(
                    __('Please verify your email address before logging in.')
                );
            }
            throw new AuthenticationException(
                __('Unable to process login. Please try again.')
            );
        }

        if (!$result) {
            return $result;
        }

        // Generate refresh token
        try {
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
        } catch (\Exception $e) {
            // If refresh token generation fails, still return the access token
            // User can login, just won't have refresh capability
            return [
                'access_token'  => $result,
                'refresh_token' => null
            ];
        }
    }
}
