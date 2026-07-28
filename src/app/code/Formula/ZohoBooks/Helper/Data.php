<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;

/**
 * Zoho Books configuration helper
 */
class Data extends AbstractHelper
{
    const XML_PATH_ZOHOBOOKS = 'zohobooks/general/';

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
            self::XML_PATH_ZOHOBOOKS . $field,
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
        return (bool) $this->getConfigValue('enabled', $storeId);
    }

    /**
     * Get Zoho data center region (e.g. ".in", ".com", ".eu")
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getRegion($storeId = null): ?string
    {
        return $this->getConfigValue('region', $storeId);
    }

    /**
     * Get Zoho Books organization ID
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getOrganizationId($storeId = null): ?string
    {
        return $this->getConfigValue('organization_id', $storeId);
    }

    /**
     * Get OAuth client ID
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getClientId($storeId = null): ?string
    {
        return $this->getConfigValue('client_id', $storeId);
    }

    /**
     * Get OAuth client secret (decrypted)
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getClientSecret($storeId = null): ?string
    {
        $encrypted = $this->getConfigValue('client_secret', $storeId);
        if ($encrypted) {
            return $this->encryptor->decrypt($encrypted);
        }
        return null;
    }

    /**
     * Get OAuth refresh token (decrypted)
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getRefreshToken($storeId = null): ?string
    {
        $encrypted = $this->getConfigValue('refresh_token', $storeId);
        if ($encrypted) {
            return $this->encryptor->decrypt($encrypted);
        }
        return null;
    }

    /**
     * Get the organization's 2-digit GST state code
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getOrgGstStateCode($storeId = null): ?string
    {
        return $this->getConfigValue('org_gst_state_code', $storeId);
    }

    /**
     * Get default GST rate as a float percentage (e.g. 18.0)
     *
     * @param int|null $storeId
     * @return float
     */
    public function getDefaultGstRate($storeId = null): float
    {
        $rate = $this->getConfigValue('default_gst_rate', $storeId);
        return (float) ($rate ?: 18);
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
