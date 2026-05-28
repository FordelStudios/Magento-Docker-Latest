<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Model\Api;

use Formula\LoginOtp\Api\Data\SendOtpResultInterfaceFactory;
use Formula\LoginOtp\Api\Data\VerifyOtpResultInterfaceFactory;
use Formula\LoginOtp\Api\LoginOtpInterface;
use Formula\LoginOtp\Service\CustomerFinder;
use Formula\LoginOtp\Service\LoginOtpRepository;
use Formula\LoginOtp\Service\PhoneValidator;
use Formula\LoginOtp\Service\TokenIssuer;
use Formula\LoginOtp\Service\WatiOtpSender;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class LoginOtp implements LoginOtpInterface
{
    private PhoneValidator $phoneValidator;
    private LoginOtpRepository $otpRepository;
    private WatiOtpSender $watiSender;
    private CustomerFinder $customerFinder;
    private TokenIssuer $tokenIssuer;
    private SendOtpResultInterfaceFactory $sendResultFactory;
    private VerifyOtpResultInterfaceFactory $verifyResultFactory;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;

    public function __construct(
        PhoneValidator $phoneValidator,
        LoginOtpRepository $otpRepository,
        WatiOtpSender $watiSender,
        CustomerFinder $customerFinder,
        TokenIssuer $tokenIssuer,
        SendOtpResultInterfaceFactory $sendResultFactory,
        VerifyOtpResultInterfaceFactory $verifyResultFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->phoneValidator = $phoneValidator;
        $this->otpRepository = $otpRepository;
        $this->watiSender = $watiSender;
        $this->customerFinder = $customerFinder;
        $this->tokenIssuer = $tokenIssuer;
        $this->sendResultFactory = $sendResultFactory;
        $this->verifyResultFactory = $verifyResultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    public function requestOtp(string $phone, ?string $mode = null)
    {
        $normalized = $this->phoneValidator->normalize($phone);
        $this->enforceMode($normalized, $mode);
        $issued = $this->otpRepository->issue($normalized);

        $sendResult = $this->watiSender->send($normalized, $issued['otp']);

        if (empty($sendResult['success'])) {
            // Roll back so the failed delivery doesn't burn a rate-limit slot.
            $this->otpRepository->deleteById($issued['otp_id']);
            throw new LocalizedException(
                __('Could not send OTP. Please try again in a moment.')
            );
        }

        if (!empty($sendResult['message_id'])) {
            $this->otpRepository->setWatiMessageId($issued['otp_id'], (string) $sendResult['message_id']);
        }

        /** @var \Formula\LoginOtp\Api\Data\SendOtpResultInterface $result */
        $result = $this->sendResultFactory->create();
        $result->setSuccess(true);
        $result->setExpiresIn($issued['expires_in']);
        $result->setMessage('OTP sent via WhatsApp.');
        return $result;
    }

    public function verifyOtp(
        string $phone,
        string $otp,
        ?string $firstname = null,
        ?string $lastname = null,
        ?string $mode = null
    ) {
        $normalized = $this->phoneValidator->normalize($phone);

        // Re-check mode at verify too: between requestOtp and verifyOtp the
        // user could have been created/deleted by a concurrent flow, so we
        // can't trust the requestOtp gate alone.
        $this->enforceMode($normalized, $mode);

        $this->otpRepository->verify($normalized, $otp);

        // firstname/lastname only matter on the create branch; CustomerFinder
        // ignores them for existing customers so /sign-up never clobbers a
        // returning user's name.
        $found = $this->customerFinder->findOrCreateByPhone(
            $normalized,
            $firstname,
            $lastname
        );
        $customer = $found['customer'];
        $tokens = $this->tokenIssuer->issueFor((int) $customer->getId());

        $hasPlaceholder = $this->isPlaceholderEmail((string) $customer->getEmail());

        /** @var \Formula\LoginOtp\Api\Data\VerifyOtpResultInterface $result */
        $result = $this->verifyResultFactory->create();
        $result->setAccessToken($tokens['access_token']);
        $result->setRefreshToken($tokens['refresh_token']);
        $result->setIsNewUser($found['is_new']);
        $result->setCustomerId((int) $customer->getId());
        $result->setHasPlaceholderEmail($hasPlaceholder);
        return $result;
    }

    /**
     * Reject early if the phone's existence on file doesn't match the caller's
     * intent. Skipped when $mode is null so legacy find-or-create callers keep
     * working unchanged.
     *
     * - mode='login'   : must exist
     * - mode='register': must NOT exist
     */
    private function enforceMode(string $normalizedPhone, ?string $mode): void
    {
        if ($mode === null || $mode === '') {
            return;
        }
        if ($mode !== 'login' && $mode !== 'register') {
            throw new LocalizedException(
                __('Invalid auth mode. Expected "login" or "register".')
            );
        }

        $existing = $this->customerFinder->findByPhone($normalizedPhone);

        if ($mode === 'login' && !$existing) {
            throw new LocalizedException(
                __('No account is registered with this number. Create an account to continue.')
            );
        }

        if ($mode === 'register' && $existing) {
            throw new LocalizedException(
                __('This number is already registered. Sign in instead.')
            );
        }
    }

    private function isPlaceholderEmail(string $email): bool
    {
        $domain = (string) $this->scopeConfig->getValue(
            CustomerFinder::XML_PATH_PLACEHOLDER_DOMAIN,
            ScopeInterface::SCOPE_STORE
        ) ?: 'formula.placeholder';
        return str_ends_with(strtolower($email), '@' . strtolower($domain));
    }
}
