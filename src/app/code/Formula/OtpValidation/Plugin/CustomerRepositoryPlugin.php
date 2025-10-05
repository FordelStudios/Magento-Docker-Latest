<?php
namespace Formula\OtpValidation\Plugin;

use Formula\OtpValidation\Service\OtpService;
use Formula\OtpValidation\Service\SmsProviderFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CustomerRepositoryPlugin
{
    const XML_PATH_OTP_ENABLED = 'formula_otp/general/enabled';

    protected $otpService;
    protected $smsProviderFactory;
    protected $scopeConfig;

    public function __construct(
        OtpService $otpService,
        SmsProviderFactory $smsProviderFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->otpService = $otpService;
        $this->smsProviderFactory = $smsProviderFactory;
        $this->scopeConfig = $scopeConfig;
    }

    public function beforeSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $customer,
        $passwordHash = null
    ) {
        $isOtpEnabled = $this->scopeConfig->getValue(
            self::XML_PATH_OTP_ENABLED,
            ScopeInterface::SCOPE_STORE
        );

        if (!$isOtpEnabled) {
            return [$customer, $passwordHash];
        }

        $customerId = $customer->getId();
        if (!$customerId) {
            return [$customer, $passwordHash];
        }

        $addresses = $customer->getAddresses();
        if (!$addresses) {
            return [$customer, $passwordHash];
        }

        $unverifiedPhones = [];

        foreach ($addresses as $address) {
            $telephone = $address->getTelephone();

            if (!$telephone) {
                continue;
            }

            $smsService = $this->smsProviderFactory->create();
            if (!$smsService->isValidIndianMobile($telephone)) {
                throw new LocalizedException(
                    __('Invalid phone number format: %1', $telephone)
                );
            }

            $extensionAttributes = $address->getExtensionAttributes();
            $isVerified = $extensionAttributes && $extensionAttributes->getIsVerified();

            if (!$isVerified && !$this->otpService->isPhoneVerified($customerId, $telephone)) {
                $unverifiedPhones[] = $telephone;
            }
        }

        if (!empty($unverifiedPhones)) {
            throw new LocalizedException(
                __(
                    'Phone number verification required for: %1. Please verify via OTP before saving address.',
                    implode(', ', $unverifiedPhones)
                )
            );
        }

        return [$customer, $passwordHash];
    }

    public function afterGet(
        CustomerRepositoryInterface $subject,
        CustomerInterface $customer
    ) {
        $this->addVerificationStatusToAddresses($customer);
        return $customer;
    }

    public function afterGetById(
        CustomerRepositoryInterface $subject,
        CustomerInterface $customer,
        $customerId
    ) {
        $this->addVerificationStatusToAddresses($customer);
        return $customer;
    }

    protected function addVerificationStatusToAddresses(CustomerInterface $customer)
    {
        $addresses = $customer->getAddresses();
        if (!$addresses) {
            return;
        }

        foreach ($addresses as $address) {
            $telephone = $address->getTelephone();
            if (!$telephone) {
                continue;
            }

            $isVerified = $this->otpService->isPhoneVerified($customer->getId(), $telephone);

            $extensionAttributes = $address->getExtensionAttributes();
            if (!$extensionAttributes) {
                $extensionAttributes = new \Magento\Framework\DataObject();
            }

            $extensionAttributes->setIsVerified($isVerified);
            $address->setExtensionAttributes($extensionAttributes);
        }
    }
}