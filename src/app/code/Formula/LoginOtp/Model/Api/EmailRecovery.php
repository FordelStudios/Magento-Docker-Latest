<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Model\Api;

use Formula\LoginOtp\Api\Data\SendOtpResultInterfaceFactory;
use Formula\LoginOtp\Api\Data\VerifyEmailOtpResultInterfaceFactory;
use Formula\LoginOtp\Api\Data\VerifyOtpResultInterfaceFactory;
use Formula\LoginOtp\Api\EmailRecoveryInterface;
use Formula\LoginOtp\Service\CustomerFinder;
use Formula\LoginOtp\Service\EmailOtpSender;
use Formula\LoginOtp\Service\EmailRecoveryOtpRepository;
use Formula\LoginOtp\Service\LoginOtpRepository;
use Formula\LoginOtp\Service\PhoneValidator;
use Formula\LoginOtp\Service\TokenIssuer;
use Formula\LoginOtp\Service\WatiOtpSender;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class EmailRecovery implements EmailRecoveryInterface
{
    private EmailRecoveryOtpRepository $emailOtpRepo;
    private EmailOtpSender $emailSender;
    private LoginOtpRepository $loginOtpRepo;
    private WatiOtpSender $watiSender;
    private PhoneValidator $phoneValidator;
    private CustomerFinder $customerFinder;
    private TokenIssuer $tokenIssuer;
    private SendOtpResultInterfaceFactory $sendResultFactory;
    private VerifyEmailOtpResultInterfaceFactory $verifyEmailResultFactory;
    private VerifyOtpResultInterfaceFactory $verifyResultFactory;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;

    public function __construct(
        EmailRecoveryOtpRepository $emailOtpRepo,
        EmailOtpSender $emailSender,
        LoginOtpRepository $loginOtpRepo,
        WatiOtpSender $watiSender,
        PhoneValidator $phoneValidator,
        CustomerFinder $customerFinder,
        TokenIssuer $tokenIssuer,
        SendOtpResultInterfaceFactory $sendResultFactory,
        VerifyEmailOtpResultInterfaceFactory $verifyEmailResultFactory,
        VerifyOtpResultInterfaceFactory $verifyResultFactory,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->emailOtpRepo = $emailOtpRepo;
        $this->emailSender = $emailSender;
        $this->loginOtpRepo = $loginOtpRepo;
        $this->watiSender = $watiSender;
        $this->phoneValidator = $phoneValidator;
        $this->customerFinder = $customerFinder;
        $this->tokenIssuer = $tokenIssuer;
        $this->sendResultFactory = $sendResultFactory;
        $this->verifyEmailResultFactory = $verifyEmailResultFactory;
        $this->verifyResultFactory = $verifyResultFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    public function requestEmailOtp(string $email)
    {
        $email = strtolower(trim($email));

        /** @var \Formula\LoginOtp\Api\Data\SendOtpResultInterface $result */
        $result = $this->sendResultFactory->create();

        // Don't leak account existence. Always return success for valid-looking
        // emails, only issue+send if a matching customer actually exists.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new LocalizedException(__('Please enter a valid email address.'));
        }

        $customer = $this->customerFinder->findByEmail($email);
        if ($customer) {
            $issued = $this->emailOtpRepo->issue($email);
            $expiryMinutes = max(1, (int) round($issued['expires_in'] / 60));
            $sendResult = $this->emailSender->send($email, $issued['otp'], $expiryMinutes);

            if (empty($sendResult['success'])) {
                $this->emailOtpRepo->deleteById($issued['otp_id']);
                throw new LocalizedException(__('Could not send recovery email. Please try again.'));
            }

            $result->setExpiresIn($issued['expires_in']);
        } else {
            // Fake expires_in to keep response shape consistent.
            $result->setExpiresIn(300);
            $this->logger->info('Formula\LoginOtp recovery requested for unknown email', ['email' => $email]);
        }

        $result->setSuccess(true);
        $result->setMessage('If an account exists for that email, a code has been sent.');
        return $result;
    }

    public function verifyEmailOtp(string $email, string $otp)
    {
        $email = strtolower(trim($email));
        $phoneAddToken = $this->emailOtpRepo->verifyAndIssueAddPhoneToken($email, $otp);

        /** @var \Formula\LoginOtp\Api\Data\VerifyEmailOtpResultInterface $result */
        $result = $this->verifyEmailResultFactory->create();
        $result->setPhoneAddToken($phoneAddToken);
        $result->setExpiresIn(300);
        return $result;
    }

    public function addPhoneRequest(string $phoneAddToken, string $phone)
    {
        // Validate the token without consuming it. Consumption happens in
        // addPhoneConfirm so a failed phone-OTP doesn't force the user back
        // to the email step.
        $this->emailOtpRepo->peekPhoneAddToken($phoneAddToken);

        $normalized = $this->phoneValidator->normalize($phone);

        // Reject early if phone is already on another account.
        $other = $this->customerFinder->findByPhone($normalized);
        if ($other) {
            throw new LocalizedException(
                __('That phone number is already linked to another account.')
            );
        }

        $issued = $this->loginOtpRepo->issue($normalized);
        $sendResult = $this->watiSender->send($normalized, $issued['otp']);
        if (empty($sendResult['success'])) {
            $this->loginOtpRepo->deleteById($issued['otp_id']);
            throw new LocalizedException(__('Could not send OTP. Please try again in a moment.'));
        }
        if (!empty($sendResult['message_id'])) {
            $this->loginOtpRepo->setWatiMessageId($issued['otp_id'], (string) $sendResult['message_id']);
        }

        /** @var \Formula\LoginOtp\Api\Data\SendOtpResultInterface $result */
        $result = $this->sendResultFactory->create();
        $result->setSuccess(true);
        $result->setExpiresIn($issued['expires_in']);
        $result->setMessage('OTP sent via WhatsApp.');
        return $result;
    }

    public function addPhoneConfirm(string $phoneAddToken, string $phone, string $otp)
    {
        // Consume the email-recovery token (one-use, expires).
        $email = $this->emailOtpRepo->consumePhoneAddToken($phoneAddToken);

        $normalized = $this->phoneValidator->normalize($phone);
        $this->loginOtpRepo->verify($normalized, $otp);

        $customer = $this->customerFinder->findByEmail($email);
        if (!$customer) {
            // Customer disappeared between recovery flow steps? Shouldn't happen
            // but log loudly if it does.
            $this->logger->error('Formula\LoginOtp: addPhoneConfirm could not find customer', [
                'email' => $email,
            ]);
            throw new LocalizedException(__('Account not found. Please restart the recovery flow.'));
        }

        $customer = $this->customerFinder->setPhoneOnCustomer($customer, $normalized);
        $tokens = $this->tokenIssuer->issueFor((int) $customer->getId());

        /** @var \Formula\LoginOtp\Api\Data\VerifyOtpResultInterface $result */
        $result = $this->verifyResultFactory->create();
        $result->setAccessToken($tokens['access_token']);
        $result->setRefreshToken($tokens['refresh_token']);
        $result->setIsNewUser(false);
        $result->setCustomerId((int) $customer->getId());
        $result->setHasPlaceholderEmail($this->isPlaceholderEmail((string) $customer->getEmail()));
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
