<?php
/**
 * Helper Class for additional functionality (Optional)
 * File: src/app/code/Formula/Shiprocket/Helper/Data.php
 */
namespace Formula\Shiprocket\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_SHIPROCKET = 'shiprocket/general/';

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * Get config value
     *
     * @param string $field
     * @param string|null $storeId
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SHIPROCKET . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if module is enabled
     *
     * @param string|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return (bool) $this->getConfigValue('enabled', $storeId);
    }

    /**
     * Get Shiprocket email
     *
     * @param string|null $storeId
     * @return string
     */
    public function getEmail($storeId = null)
    {
        return $this->getConfigValue('email', $storeId);
    }

    /**
     * Get Shiprocket password
     *
     * @param string|null $storeId
     * @return string
     */
    public function getPassword($storeId = null)
    {
        return $this->getConfigValue('password', $storeId);
    }

    /**
     * Get pickup postcode
     *
     * @param string|null $storeId
     * @return string
     */
    public function getPickupPostcode($storeId = null)
    {
        return $this->getConfigValue('pickup_postcode', $storeId);
    }

    /**
     * Check if debug mode is enabled
     *
     * @param string|null $storeId
     * @return bool
     */
    public function isDebugMode($storeId = null)
    {
        return (bool) $this->getConfigValue('debug_mode', $storeId);
    }

    /**
     * Get webhook secret key
     *
     * @param string|null $storeId
     * @return string|null
     */
    public function getWebhookSecretKey($storeId = null)
    {
        return $this->getConfigValue('webhook_secret_key', $storeId);
    }
}