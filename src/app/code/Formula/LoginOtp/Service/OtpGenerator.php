<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class OtpGenerator
{
    public const XML_PATH_OTP_LENGTH = 'formula_login_otp/general/otp_length';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Generate a fresh numeric OTP. Uses random_int for cryptographic strength.
     * Default length is 6 digits.
     */
    public function generate(): string
    {
        $length = $this->getLength();
        $max = (10 ** $length) - 1;
        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    private function getLength(): int
    {
        $length = (int) $this->scopeConfig->getValue(self::XML_PATH_OTP_LENGTH, ScopeInterface::SCOPE_STORE);
        return $length > 0 ? $length : 6;
    }
}
