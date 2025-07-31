<?php
namespace Formula\RefreshToken\Plugin;

use Formula\RefreshToken\Model\RefreshTokenFactory;
use Formula\RefreshToken\Model\ResourceModel\RefreshToken as RefreshTokenResource;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Math\Random;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class CustomerTokenPlugin
{
    protected $tokenFactory;
    protected $tokenResource;
    protected $dateTime;
    protected $random;
    protected $customerRepository;

    public function __construct(
        RefreshTokenFactory $tokenFactory,
        RefreshTokenResource $tokenResource,
        DateTime $dateTime,
        Random $random,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->tokenResource = $tokenResource;
        $this->dateTime = $dateTime;
        $this->random = $random;
        $this->customerRepository = $customerRepository;
    }

    public function afterCreateCustomerAccessToken($subject, $result, $username = null, $password = null)
    {
        if (!$result) {
            return $result; // No token generated
        }

        try {
            $customer = $this->customerRepository->get($username);
            $customerId = $customer->getId();
        } catch (NoSuchEntityException $e) {
            return $result; // fallback if customer not found
        }

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
