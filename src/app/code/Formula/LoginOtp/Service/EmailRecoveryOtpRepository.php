<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Service;

use Formula\LoginOtp\Model\EmailRecoveryOtpFactory;
use Formula\LoginOtp\Model\ResourceModel\EmailRecoveryOtp as EmailRecoveryOtpResource;
use Formula\LoginOtp\Model\ResourceModel\EmailRecoveryOtp\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use Magento\Store\Model\ScopeInterface;

/**
 * Same lifecycle as LoginOtpRepository but keyed on email and with an extra
 * "phone_add_token" that's issued after a successful verify. The frontend uses
 * that token to call /V1/auth/recover/add-phone, which is the only path through
 * which a legacy customer can attach a phone to their account.
 *
 * Token expiry: 5 minutes after issue. If the user takes longer than that to
 * receive their phone OTP and submit it, they restart the recovery flow.
 */
class EmailRecoveryOtpRepository
{
    public const XML_PATH_EXPIRY_SECONDS = 'formula_login_otp/general/expiry_seconds';
    public const XML_PATH_MAX_ATTEMPTS = 'formula_login_otp/general/max_attempts_per_otp';
    public const XML_PATH_MAX_REQUESTS = 'formula_login_otp/general/max_requests_per_phone_per_window';
    public const XML_PATH_RATE_WINDOW = 'formula_login_otp/general/rate_limit_window_seconds';
    private const PHONE_ADD_TOKEN_TTL_SECONDS = 300;

    private EmailRecoveryOtpFactory $otpFactory;
    private EmailRecoveryOtpResource $otpResource;
    private CollectionFactory $collectionFactory;
    private ScopeConfigInterface $scopeConfig;
    private OtpGenerator $otpGenerator;
    private Random $random;

    public function __construct(
        EmailRecoveryOtpFactory $otpFactory,
        EmailRecoveryOtpResource $otpResource,
        CollectionFactory $collectionFactory,
        ScopeConfigInterface $scopeConfig,
        OtpGenerator $otpGenerator,
        Random $random
    ) {
        $this->otpFactory = $otpFactory;
        $this->otpResource = $otpResource;
        $this->collectionFactory = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->otpGenerator = $otpGenerator;
        $this->random = $random;
    }

    /**
     * @return array{otp: string, otp_id: int, expires_in: int}
     */
    public function issue(string $email): array
    {
        $this->assertWithinRateLimit($email);

        $otpCode = $this->otpGenerator->generate();
        $expirySeconds = $this->getExpirySeconds();
        $expiresAt = date('Y-m-d H:i:s', time() + $expirySeconds);

        $otp = $this->otpFactory->create();
        $otp->setData([
            'email' => $email,
            'otp_code' => $otpCode,
            'expires_at' => $expiresAt,
            'verify_attempts' => 0,
            'is_verified' => false,
        ]);
        $this->otpResource->save($otp);

        return [
            'otp' => $otpCode,
            'otp_id' => (int) $otp->getId(),
            'expires_in' => $expirySeconds,
        ];
    }

    public function deleteById(int $otpId): void
    {
        $otp = $this->otpFactory->create();
        $this->otpResource->load($otp, $otpId);
        if ($otp->getId()) {
            $this->otpResource->delete($otp);
        }
    }

    /**
     * Verify OTP and mint a short-lived `phone_add_token` the caller can use
     * to authorize a follow-up /add-phone call.
     */
    public function verifyAndIssueAddPhoneToken(string $email, string $submittedOtp): string
    {
        $otp = $this->findActive($email);
        if (!$otp || !$otp->getId()) {
            throw new LocalizedException(__('No active OTP for this email. Please request a new one.'));
        }

        $maxAttempts = $this->getMaxAttempts();
        if ((int) $otp->getData('verify_attempts') >= $maxAttempts) {
            throw new LocalizedException(__('Too many failed attempts. Please request a new OTP.'));
        }

        if (strtotime($otp->getData('expires_at')) < time()) {
            throw new LocalizedException(__('OTP has expired. Please request a new one.'));
        }

        if (!hash_equals((string) $otp->getData('otp_code'), $submittedOtp)) {
            $otp->setData('verify_attempts', (int) $otp->getData('verify_attempts') + 1);
            $this->otpResource->save($otp);
            throw new LocalizedException(__('Incorrect OTP. Please try again.'));
        }

        $phoneAddToken = $this->random->getUniqueHash();
        $otp->setData('is_verified', true);
        $otp->setData('phone_add_token', $phoneAddToken);
        $otp->setData('phone_add_token_expires_at', date('Y-m-d H:i:s', time() + self::PHONE_ADD_TOKEN_TTL_SECONDS));
        $this->otpResource->save($otp);

        return $phoneAddToken;
    }

    /**
     * Validate a phone_add_token without consuming it. Returns the email it
     * authorizes. Used by /add-phone-request so a failed phone-OTP send doesn't
     * force the user all the way back to the email step.
     */
    public function peekPhoneAddToken(string $phoneAddToken): string
    {
        $row = $this->findByPhoneAddToken($phoneAddToken);
        return (string) $row->getData('email');
    }

    /**
     * Resolve a phone_add_token to the email it authorizes. Consumes the token
     * (one-use) on success. Returns the email or throws.
     */
    public function consumePhoneAddToken(string $phoneAddToken): string
    {
        $otp = $this->findByPhoneAddToken($phoneAddToken);
        $email = (string) $otp->getData('email');

        // Consume — clear the token so it can't be reused.
        $otp->setData('phone_add_token', null);
        $otp->setData('phone_add_token_expires_at', null);
        $this->otpResource->save($otp);

        return $email;
    }

    /**
     * Locate the email-recovery row a phone_add_token points at, throwing if
     * missing or expired. Doesn't mutate state; both peek and consume use this.
     */
    private function findByPhoneAddToken(string $phoneAddToken)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('phone_add_token', $phoneAddToken)
                   ->setPageSize(1);
        $otp = $collection->getFirstItem();

        if (!$otp->getId()) {
            throw new LocalizedException(__('Invalid or expired recovery token.'));
        }
        if (strtotime((string) $otp->getData('phone_add_token_expires_at')) < time()) {
            throw new LocalizedException(__('Recovery token has expired. Please restart the recovery flow.'));
        }

        return $otp;
    }

    private function findActive(string $email)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('email', $email)
                   ->addFieldToFilter('is_verified', false)
                   ->setOrder('created_at', 'DESC')
                   ->setPageSize(1);
        return $collection->getFirstItem();
    }

    private function assertWithinRateLimit(string $email): void
    {
        $maxRequests = $this->getMaxRequests();
        $windowSeconds = $this->getRateWindowSeconds();
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);

        $count = (int) $this->collectionFactory->create()
            ->addFieldToFilter('email', $email)
            ->addFieldToFilter('created_at', ['gteq' => $since])
            ->getSize();

        if ($count >= $maxRequests) {
            throw new LocalizedException(
                __('Too many recovery requests. Please wait a few minutes and try again.')
            );
        }
    }

    private function getExpirySeconds(): int
    {
        $v = (int) $this->scopeConfig->getValue(self::XML_PATH_EXPIRY_SECONDS, ScopeInterface::SCOPE_STORE);
        return $v > 0 ? $v : 300;
    }

    private function getMaxAttempts(): int
    {
        $v = (int) $this->scopeConfig->getValue(self::XML_PATH_MAX_ATTEMPTS, ScopeInterface::SCOPE_STORE);
        return $v > 0 ? $v : 5;
    }

    private function getMaxRequests(): int
    {
        $v = (int) $this->scopeConfig->getValue(self::XML_PATH_MAX_REQUESTS, ScopeInterface::SCOPE_STORE);
        return $v > 0 ? $v : 3;
    }

    private function getRateWindowSeconds(): int
    {
        $v = (int) $this->scopeConfig->getValue(self::XML_PATH_RATE_WINDOW, ScopeInterface::SCOPE_STORE);
        return $v > 0 ? $v : 600;
    }
}
