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

    public function requestOtp(string $phone)
    {
        $normalized = $this->phoneValidator->normalize($phone);
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

    public function verifyOtp(string $phone, string $otp)
    {
        $normalized = $this->phoneValidator->normalize($phone);
        $this->otpRepository->verify($normalized, $otp);

        // OTP is good — issue token (find-or-create customer).
        $found = $this->customerFinder->findOrCreateByPhone($normalized);
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

    private function isPlaceholderEmail(string $email): bool
    {
        $domain = (string) $this->scopeConfig->getValue(
            CustomerFinder::XML_PATH_PLACEHOLDER_DOMAIN,
            ScopeInterface::SCOPE_STORE
        ) ?: 'formula.placeholder';
        return str_ends_with(strtolower($email), '@' . strtolower($domain));
    }
}
