<?php
namespace Formula\RazorpayApi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_WEBHOOK_SECRET = 'formula_razorpayapi/webhook/secret';
    const XML_PATH_WEBHOOK_ENABLED = 'formula_razorpayapi/webhook/enabled';

    /** @var EncryptorInterface */
    private $encryptor;

    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /**
     * Get webhook secret from admin config (decrypted)
     *
     * @return string|null
     */
    public function getWebhookSecret()
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_WEBHOOK_SECRET,
            ScopeInterface::SCOPE_STORE
        );
        return $value ? $this->encryptor->decrypt($value) : null;
    }

    /**
     * Check if webhook handling is enabled
     *
     * @return bool
     */
    public function isWebhookEnabled()
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_WEBHOOK_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Razorpay key ID from payment config
     *
     * @return string|null
     */
    public function getRazorpayKeyId()
    {
        return $this->scopeConfig->getValue(
            'payment/razorpay/key_id',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Razorpay key secret from payment config
     *
     * @return string|null
     */
    public function getRazorpayKeySecret()
    {
        return $this->scopeConfig->getValue(
            'payment/razorpay/key_secret',
            ScopeInterface::SCOPE_STORE
        );
    }
}
