<?php
namespace Formula\RefreshToken\Model;

use Formula\RefreshToken\Api\RefreshTokenRepositoryInterface;
use Formula\RefreshToken\Model\RefreshTokenFactory;
use Formula\RefreshToken\Model\ResourceModel\RefreshToken as RefreshTokenResource;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;
use Magento\Framework\Exception\AuthenticationException;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    protected $tokenFactory;
    protected $tokenResource;
    protected $tokenModelFactory;

    public function __construct(
        RefreshTokenFactory $tokenFactory,
        RefreshTokenResource $tokenResource,
        TokenModelFactory $tokenModelFactory
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->tokenResource = $tokenResource;
        $this->tokenModelFactory = $tokenModelFactory;
    }

    /**
     * Generate a new customer token using a refresh token
     *
     * @param string $refreshToken
     * @return string[]
     */
    public function refresh($refreshToken)
    {
        $tokenModel = $this->tokenFactory->create();
        $this->tokenResource->load($tokenModel, $refreshToken, 'token');

        if (!$tokenModel->getId()) {
            throw new AuthenticationException(__('Invalid refresh token.'));
        }

        if (strtotime($tokenModel->getExpiresAt()) < time()) {
            throw new AuthenticationException(__('Refresh token expired.'));
        }

        // Issue new JWT using customer ID
        $customerId = $tokenModel->getCustomerId();
        $token = $this->tokenModelFactory->create()->createCustomerToken($customerId)->getToken();
        
        return ['access_token' => $token];
    }
}