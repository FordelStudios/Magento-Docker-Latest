<?php
declare(strict_types=1);

namespace Formula\Wati\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Wati configuration helper
 */
class Data extends AbstractHelper
{
    const XML_PATH_WATI = 'formula_wati/';

    /**
     * Status to template config key mapping
     */
    const STATUS_TEMPLATE_MAP = [
        'pending' => 'order_placed',
        'pending_payment' => 'order_placed',
        'processing' => 'order_processing',
        'shipped' => 'order_shipped',
        'in_transit' => 'order_shipped',
        'out_for_delivery' => 'out_for_delivery',
        'delivered' => 'order_delivered',
        'complete' => 'order_delivered',
        'canceled' => 'order_cancelled',
        'cancelled' => 'order_cancelled',
        'closed' => 'order_refunded',
        'refunded' => 'order_refunded',
    ];

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    /**
     * Get config value
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_WATI . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool) $this->getConfigValue('general/enabled', $storeId);
    }

    /**
     * Get API endpoint
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getApiEndpoint($storeId = null): ?string
    {
        $endpoint = $this->getConfigValue('general/api_endpoint', $storeId);
        if ($endpoint) {
            $endpoint = rtrim($endpoint, '/');
        }
        return $endpoint;
    }

    /**
     * Get API token (decrypted)
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getApiToken($storeId = null): ?string
    {
        $encryptedToken = $this->getConfigValue('general/api_token', $storeId);
        if ($encryptedToken) {
            return $this->encryptor->decrypt($encryptedToken);
        }
        return null;
    }

    /**
     * Get webhook secret
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getWebhookSecret($storeId = null): ?string
    {
        return $this->getConfigValue('general/webhook_secret', $storeId);
    }

    /**
     * Check if debug mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugMode($storeId = null): bool
    {
        return (bool) $this->getConfigValue('general/debug_mode', $storeId);
    }

    /**
     * Get max retry attempts
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxRetries($storeId = null): int
    {
        $retries = $this->getConfigValue('retry/max_retries', $storeId);
        return (int) ($retries ?: 3);
    }

    /**
     * Get template name for order status
     *
     * @param string $status
     * @param int|null $storeId
     * @return string|null
     */
    public function getTemplateForStatus($status, $storeId = null): ?string
    {
        $status = strtolower($status);
        $templateKey = self::STATUS_TEMPLATE_MAP[$status] ?? null;

        if (!$templateKey) {
            return null;
        }

        return $this->getConfigValue('templates/' . $templateKey, $storeId);
    }

    /**
     * Format phone number for WhatsApp
     * Handles Indian phone numbers and adds country code if needed
     *
     * @param string $phoneNumber
     * @return string
     */
    public function formatPhoneForWhatsApp($phoneNumber): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Handle Indian numbers (10 digits starting with 6-9)
        if (strlen($phone) === 10 && preg_match('/^[6-9]/', $phone)) {
            return '91' . $phone;
        }

        // If number starts with 0, remove it and add 91 (India)
        if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
            return '91' . substr($phone, 1);
        }

        // Already has country code
        if (strlen($phone) >= 11) {
            return $phone;
        }

        return $phone;
    }

    /**
     * Check if a status should trigger notification
     *
     * @param string $status
     * @return bool
     */
    public function isNotifiableStatus($status): bool
    {
        $status = strtolower($status);
        return isset(self::STATUS_TEMPLATE_MAP[$status]);
    }
}
