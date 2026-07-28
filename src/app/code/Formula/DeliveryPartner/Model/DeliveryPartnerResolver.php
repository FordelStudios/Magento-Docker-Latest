<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Model;

use Formula\DeliveryPartner\Api\DeliveryPartnerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Resolves which delivery-partner adapter should handle a given order.
 *
 * Precedence:
 *  1. Per-order override — order.delivery_partner, if it is a non-empty
 *     string that matches a code present in the injected partner map.
 *  2. Global default — system config delivery_partner/general/active_partner
 *     (defaults to PartnerCode::SHIPROCKET if unset).
 *
 * The resolved code MUST exist in the injected $partners map. An unresolved
 * code (misconfiguration — e.g. config points at a partner whose adapter
 * di.xml wiring was never added) throws rather than silently falling back to
 * some partner, per the "never swallow errors" rule.
 */
class DeliveryPartnerResolver
{
    private const XML_PATH_ACTIVE_PARTNER = 'delivery_partner/general/active_partner';

    /**
     * @var DeliveryPartnerInterface[]
     */
    private array $partners;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param DeliveryPartnerInterface[] $partners Keyed by partner code (DI-injected map).
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        array $partners,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->partners = $partners;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param OrderInterface $order
     * @return DeliveryPartnerInterface
     * @throws LocalizedException
     */
    public function resolve(OrderInterface $order): DeliveryPartnerInterface
    {
        $code = $this->resolveCode($order);

        if (!isset($this->partners[$code])) {
            throw new LocalizedException(__(
                'Delivery partner "%1" is not configured (no adapter registered for this code).',
                $code
            ));
        }

        return $this->partners[$code];
    }

    /**
     * Resolve just the partner code, without requiring it to exist in the map.
     *
     * @param OrderInterface $order
     * @return string
     */
    public function resolveCode(OrderInterface $order): string
    {
        $override = $order->getData('delivery_partner');

        if (is_string($override) && $override !== '' && isset($this->partners[$override])) {
            return $override;
        }

        $default = $this->scopeConfig->getValue(self::XML_PATH_ACTIVE_PARTNER);

        return is_string($default) && $default !== '' ? $default : PartnerCode::SHIPROCKET;
    }
}
