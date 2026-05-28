<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Service;

use Formula\LoginOtp\Model\LoginOtpFactory;
use Formula\LoginOtp\Model\ResourceModel\LoginOtp as LoginOtpResource;
use Formula\LoginOtp\Model\ResourceModel\LoginOtp\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;

/**
 * Owns the lifecycle of a phone-login OTP:
 *   - generate + persist + rate-limit on request
 *   - lookup + validate + mark-used + attempt-tracking on verify
 *
 * Stateless from the caller's perspective. Caller (LoginOtp Api) decides what
 * to do once an OTP verifies (find-or-create customer, issue token, etc.).
 */
class LoginOtpRepository
{
    public const XML_PATH_EXPIRY_SECONDS = 'formula_login_otp/general/expiry_seconds';
    public const XML_PATH_MAX_ATTEMPTS = 'formula_login_otp/general/max_attempts_per_otp';
    public const XML_PATH_MAX_REQUESTS = 'formula_login_otp/general/max_requests_per_phone_per_window';
    public const XML_PATH_RATE_WINDOW = 'formula_login_otp/general/rate_limit_window_seconds';

    private LoginOtpFactory $otpFactory;
    private LoginOtpResource $otpResource;
    private CollectionFactory $collectionFactory;
    private ScopeConfigInterface $scopeConfig;
    private OtpGenerator $otpGenerator;

    public function __construct(
        LoginOtpFactory $otpFactory,
        LoginOtpResource $otpResource,
        CollectionFactory $collectionFactory,
        ScopeConfigInterface $scopeConfig,
        OtpGenerator $otpGenerator
    ) {
        $this->otpFactory = $otpFactory;
        $this->otpResource = $otpResource;
        $this->collectionFactory = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->otpGenerator = $otpGenerator;
    }

    /**
     * Issue a new OTP for a phone.
     *
     * Enforces rate limit. Caller is responsible for actually delivering the
     * OTP (e.g. via WatiOtpSender) and for rolling back this row on send
     * failure (we'd otherwise count it against the user's rate limit).
     *
     * @return array{otp: string, otp_id: int, expires_at: string, expires_in: int}
     * @throws LocalizedException when rate limit exceeded
     */
    public function issue(string $normalizedPhone): array
    {
        $this->assertWithinRateLimit($normalizedPhone);

        $otpCode = $this->otpGenerator->generate();
        $expirySeconds = $this->getExpirySeconds();
        $expiresAt = date('Y-m-d H:i:s', time() + $expirySeconds);

        $otp = $this->otpFactory->create();
        $otp->setData([
            'phone' => $normalizedPhone,
            'otp_code' => $otpCode,
            'expires_at' => $expiresAt,
            'verify_attempts' => 0,
            'is_verified' => false,
        ]);
        $this->otpResource->save($otp);

        return [
            'otp' => $otpCode,
            'otp_id' => (int) $otp->getId(),
            'expires_at' => $expiresAt,
            'expires_in' => $expirySeconds,
        ];
    }

    /**
     * Delete an OTP row by id. Used to roll back when WATI send fails so the
     * user isn't charged a rate-limit slot for our delivery problem.
     */
    public function deleteById(int $otpId): void
    {
        $otp = $this->otpFactory->create();
        $this->otpResource->load($otp, $otpId);
        if ($otp->getId()) {
            $this->otpResource->delete($otp);
        }
    }

    /**
     * Attach a WATI message ID for delivery tracking. No-op if otpId is unknown.
     */
    public function setWatiMessageId(int $otpId, string $messageId): void
    {
        $otp = $this->otpFactory->create();
        $this->otpResource->load($otp, $otpId);
        if ($otp->getId()) {
            $otp->setData('wati_message_id', $messageId);
            $this->otpResource->save($otp);
        }
    }

    /**
     * Verify an OTP against the most recent unexpired-and-unverified row for
     * this phone. On success, marks the row as verified. On failure, increments
     * verify_attempts so the row self-locks after N tries.
     *
     * @throws LocalizedException with user-safe message on every failure path
     */
    public function verify(string $normalizedPhone, string $submittedOtp): void
    {
        $otp = $this->findActive($normalizedPhone);

        if (!$otp || !$otp->getId()) {
            throw new LocalizedException(__('No active OTP for this number. Please request a new one.'));
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

        $otp->setData('is_verified', true);
        $this->otpResource->save($otp);
    }

    /**
     * Most-recently-created unverified OTP for this phone.
     */
    private function findActive(string $normalizedPhone)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('phone', $normalizedPhone)
                   ->addFieldToFilter('is_verified', false)
                   ->setOrder('created_at', 'DESC')
                   ->setPageSize(1);
        return $collection->getFirstItem();
    }

    private function assertWithinRateLimit(string $normalizedPhone): void
    {
        $maxRequests = $this->getMaxRequests();
        $windowSeconds = $this->getRateWindowSeconds();
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);

        $count = (int) $this->collectionFactory->create()
            ->addFieldToFilter('phone', $normalizedPhone)
            ->addFieldToFilter('created_at', ['gteq' => $since])
            ->getSize();

        if ($count >= $maxRequests) {
            throw new LocalizedException(
                __('Too many OTP requests. Please wait a few minutes and try again.')
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
