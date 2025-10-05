<?php
namespace Formula\OtpValidation\Service;

use Formula\OtpValidation\Api\OtpRepositoryInterface;
use Formula\OtpValidation\Model\OtpFactory;
use Formula\OtpValidation\Model\ResourceModel\Otp\CollectionFactory;
use Formula\OtpValidation\Service\SmsProviderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class OtpService
{
    const XML_PATH_OTP_EXPIRY_MINUTES = 'formula_otp/general/expiry_minutes';
    const XML_PATH_OTP_LENGTH = 'formula_otp/general/otp_length';
    const XML_PATH_MAX_ATTEMPTS = 'formula_otp/general/max_attempts';

    protected $otpRepository;
    protected $otpFactory;
    protected $otpCollectionFactory;
    protected $smsProviderFactory;
    protected $scopeConfig;
    protected $dateTime;
    protected $logger;

    public function __construct(
        OtpRepositoryInterface $otpRepository,
        OtpFactory $otpFactory,
        CollectionFactory $otpCollectionFactory,
        SmsProviderFactory $smsProviderFactory,
        ScopeConfigInterface $scopeConfig,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        $this->otpRepository = $otpRepository;
        $this->otpFactory = $otpFactory;
        $this->otpCollectionFactory = $otpCollectionFactory;
        $this->smsProviderFactory = $smsProviderFactory;
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    public function generateAndSendOtp($customerId, $phoneNumber)
    {
        $smsService = $this->smsProviderFactory->create();

        if (!$smsService->isValidIndianMobile($phoneNumber)) {
            throw new LocalizedException(__('Invalid Indian mobile number format'));
        }

        if ($this->checkRateLimit($customerId, $phoneNumber)) {
            throw new LocalizedException(__('Too many OTP requests. Please try again later.'));
        }

        $otpCode = $this->generateOtpCode();
        $expiryMinutes = $this->getOtpExpiryMinutes();
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));

        $otp = $this->otpFactory->create();
        $otp->setCustomerId($customerId);
        $otp->setPhoneNumber($phoneNumber);
        $otp->setOtpCode($otpCode);
        $otp->setExpiresAt($expiresAt);
        $otp->setIsVerified(false);

        $this->otpRepository->save($otp);

        $smsResult = $smsService->sendOtp($phoneNumber, $otpCode);

        if (!$smsResult['success']) {
            $this->otpRepository->delete($otp);
            throw new LocalizedException(__('Failed to send OTP: %1', $smsResult['error']));
        }

        return [
            'success' => true,
            'message' => 'OTP sent successfully',
            'expires_in_minutes' => $expiryMinutes
        ];
    }

    public function verifyOtp($customerId, $phoneNumber, $otpCode)
    {
        $otp = $this->otpRepository->getByCustomerIdAndPhone($customerId, $phoneNumber);

        if (!$otp->getEntityId()) {
            throw new LocalizedException(__('No OTP found for this phone number'));
        }

        if ($otp->getIsVerified()) {
            throw new LocalizedException(__('OTP already verified'));
        }

        if (strtotime($otp->getExpiresAt()) < time()) {
            throw new LocalizedException(__('OTP has expired'));
        }

        if ($otp->getOtpCode() !== $otpCode) {
            throw new LocalizedException(__('Invalid OTP'));
        }

        $otp->setIsVerified(true);
        $this->otpRepository->save($otp);

        return [
            'success' => true,
            'message' => 'OTP verified successfully'
        ];
    }

    public function isPhoneVerified($customerId, $phoneNumber)
    {
        $otp = $this->otpRepository->getByCustomerIdAndPhone($customerId, $phoneNumber);

        return $otp->getEntityId() && $otp->getIsVerified();
    }

    protected function generateOtpCode()
    {
        $length = $this->getOtpLength();
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    protected function getOtpExpiryMinutes()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_OTP_EXPIRY_MINUTES,
            ScopeInterface::SCOPE_STORE
        ) ?: 5;
    }

    protected function getOtpLength()
    {
        return 4;
    }

    protected function checkRateLimit($customerId, $phoneNumber)
    {
        $maxAttempts = $this->scopeConfig->getValue(
            self::XML_PATH_MAX_ATTEMPTS,
            ScopeInterface::SCOPE_STORE
        ) ?: 3;

        $timeframe = 10;
        $since = date('Y-m-d H:i:s', strtotime("-{$timeframe} minutes"));

        $collection = $this->otpCollectionFactory->create();
        $count = $collection
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('phone_number', $phoneNumber)
            ->addFieldToFilter('created_at', ['gteq' => $since])
            ->getSize();

        return $count >= $maxAttempts;
    }
}