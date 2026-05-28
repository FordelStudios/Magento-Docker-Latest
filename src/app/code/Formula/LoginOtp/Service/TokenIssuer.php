<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Service;

use Formula\RefreshToken\Model\RefreshTokenFactory;
use Formula\RefreshToken\Model\ResourceModel\RefreshToken as RefreshTokenResource;
use Magento\Framework\Math\Random;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Integration\Model\Oauth\TokenFactory as IntegrationTokenFactory;
use Psr\Log\LoggerInterface;

/**
 * Issues an {access_token, refresh_token} pair for a known customer ID,
 * matching the contract produced by Formula\RefreshToken's password-login
 * plugin. Consumers (frontend) can keep using one auth shape regardless of
 * whether the login came via password or phone+OTP.
 *
 * The refresh-token row goes into the same `formula_refresh_token` table so
 * the existing /V1/integration/customer/refreshtoken endpoint continues to
 * work without changes.
 */
class TokenIssuer
{
    private IntegrationTokenFactory $accessTokenFactory;
    private RefreshTokenFactory $refreshTokenFactory;
    private RefreshTokenResource $refreshTokenResource;
    private Random $random;
    private DateTime $dateTime;
    private LoggerInterface $logger;

    public function __construct(
        IntegrationTokenFactory $accessTokenFactory,
        RefreshTokenFactory $refreshTokenFactory,
        RefreshTokenResource $refreshTokenResource,
        Random $random,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        $this->accessTokenFactory = $accessTokenFactory;
        $this->refreshTokenFactory = $refreshTokenFactory;
        $this->refreshTokenResource = $refreshTokenResource;
        $this->random = $random;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * @return array{access_token: string, refresh_token: string|null}
     */
    public function issueFor(int $customerId): array
    {
        $accessToken = $this->accessTokenFactory->create()
            ->createCustomerToken($customerId)
            ->getToken();

        $refreshTokenValue = null;
        try {
            $refreshTokenValue = $this->random->getUniqueHash();
            $expiresAt = $this->dateTime->gmtDate('Y-m-d H:i:s', strtotime('+30 days'));

            $refreshToken = $this->refreshTokenFactory->create();
            $refreshToken->setCustomerId($customerId);
            $refreshToken->setToken($refreshTokenValue);
            $refreshToken->setExpiresAt($expiresAt);
            $this->refreshTokenResource->save($refreshToken);
        } catch (\Exception $e) {
            // Mirror CustomerTokenPlugin: refresh failure is non-fatal, user
            // still gets a working access token (just no refresh capability).
            $this->logger->warning('Formula\LoginOtp: refresh token issue failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            $refreshTokenValue = null;
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenValue,
        ];
    }
}
