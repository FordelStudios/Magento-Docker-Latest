<?php
namespace Formula\OtpValidation\Service;

use Formula\OtpValidation\Api\SmsServiceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;

class SmsProviderFactory
{
    const XML_PATH_SMS_PROVIDER = 'formula_otp/general/sms_provider';

    const PROVIDER_MSG91 = 'msg91';
    const PROVIDER_2FACTOR = '2factor';

    protected $scopeConfig;
    protected $objectManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ObjectManagerInterface $objectManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->objectManager = $objectManager;
    }

    /**
     * Create SMS service based on configuration
     *
     * @return SmsServiceInterface
     * @throws \Exception
     */
    public function create()
    {
        $provider = $this->scopeConfig->getValue(
            self::XML_PATH_SMS_PROVIDER,
            ScopeInterface::SCOPE_STORE
        );

        if (!$provider) {
            $provider = self::PROVIDER_MSG91;
        }

        switch ($provider) {
            case self::PROVIDER_2FACTOR:
                return $this->objectManager->create(TwoFactorSmsService::class);

            case self::PROVIDER_MSG91:
            default:
                return $this->objectManager->create(Msg91SmsService::class);
        }
    }

    /**
     * Get available providers
     *
     * @return array
     */
    public function getAvailableProviders()
    {
        return [
            self::PROVIDER_MSG91 => 'MSG91',
            self::PROVIDER_2FACTOR => '2Factor'
        ];
    }
}