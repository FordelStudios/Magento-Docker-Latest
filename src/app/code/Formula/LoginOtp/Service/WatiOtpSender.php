<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Service;

use Formula\Wati\Service\WatiApiService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Sends a login OTP over WhatsApp via the Formula_Wati module.
 *
 * Template: `formula_login_otp` (approved by Meta as an Authentication-category
 * template — body locked to "{{1}} is your verification code. For your security,
 * do not share this code." with a "Copy code" button).
 */
class WatiOtpSender
{
    public const XML_PATH_TEMPLATE = 'formula_login_otp/general/wati_template_name';

    private WatiApiService $watiApi;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;

    public function __construct(
        WatiApiService $watiApi,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->watiApi = $watiApi;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * @param string $normalizedPhone 10-digit Indian mobile (no country code)
     * @param string $otp             The numeric OTP to deliver
     * @return array{success: bool, message_id?: string, error?: string}
     */
    public function send(string $normalizedPhone, string $otp): array
    {
        // WATI expects E.164 without the '+'. India = 91 + 10-digit.
        $whatsappNumber = '91' . $normalizedPhone;

        $templateName = (string) $this->scopeConfig->getValue(
            self::XML_PATH_TEMPLATE,
            ScopeInterface::SCOPE_STORE
        ) ?: 'formula_login_otp';

        // Authentication templates have a single positional parameter {{1}} = OTP.
        $parameters = [
            ['name' => '1', 'value' => $otp],
        ];

        $result = $this->watiApi->sendTemplateMessage($whatsappNumber, $templateName, $parameters);

        if (empty($result['success'])) {
            $this->logger->error('Formula\LoginOtp WATI send failed', [
                'phone' => $normalizedPhone,
                'template' => $templateName,
                'error' => $result['error'] ?? 'unknown',
            ]);
        }

        return $result;
    }
}
