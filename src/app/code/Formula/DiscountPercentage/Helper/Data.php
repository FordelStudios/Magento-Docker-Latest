<?php
namespace Formula\DiscountPercentage\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * Config path for debug mode
     */
    const XML_PATH_DEBUG_MODE = 'formula_discount/general/debug_mode';

    /**
     * Check if debug mode is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDebugModeEnabled($storeId = null)
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_DEBUG_MODE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
