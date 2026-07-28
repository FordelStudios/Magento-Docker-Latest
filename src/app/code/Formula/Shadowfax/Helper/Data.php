<?php
declare(strict_types=1);

/**
 * Helper Class for ShadowFax admin configuration
 * File: src/app/code/Formula/Shadowfax/Helper/Data.php
 */
namespace Formula\Shadowfax\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_SHADOWFAX = 'shadowfax/general/';

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
            self::XML_PATH_SHADOWFAX . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if ShadowFax integration is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool) $this->getConfigValue('enabled', $storeId);
    }

    /**
     * Get API base URL
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getApiBaseUrl($storeId = null): ?string
    {
        $url = $this->getConfigValue('api_base_url', $storeId);
        if ($url) {
            $url = rtrim($url, '/');
        }
        return $url;
    }

    /**
     * Get API token (decrypted)
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getApiToken($storeId = null): ?string
    {
        $encryptedToken = $this->getConfigValue('api_token', $storeId);
        if ($encryptedToken) {
            return $this->encryptor->decrypt($encryptedToken);
        }
        return null;
    }

    /**
     * Get client name registered with ShadowFax
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getClientName($storeId = null): ?string
    {
        return $this->getConfigValue('client_name', $storeId);
    }

    /**
     * Get pickup location name
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getPickupLocation($storeId = null): ?string
    {
        return $this->getConfigValue('pickup_location', $storeId);
    }

    /**
     * Get pickup postcode
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getPickupPostcode($storeId = null): ?string
    {
        return $this->getConfigValue('pickup_postcode', $storeId);
    }

    /**
     * Get webhook secret (decrypted)
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getWebhookSecret($storeId = null): ?string
    {
        $encryptedSecret = $this->getConfigValue('webhook_secret', $storeId);
        if ($encryptedSecret) {
            return $this->encryptor->decrypt($encryptedSecret);
        }
        return null;
    }

    /**
     * Check if debug mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugMode($storeId = null): bool
    {
        return (bool) $this->getConfigValue('debug_mode', $storeId);
    }
}
