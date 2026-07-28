<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Model;

/**
 * Single source of truth for delivery-partner code strings.
 *
 * Adapters (Shiprocket/ShadowFax), the resolver, and the admin config source
 * model all reference these constants instead of hardcoding string literals,
 * so the code strings only ever exist in one place.
 */
class PartnerCode
{
    public const SHIPROCKET = 'shiprocket';

    public const SHADOWFAX = 'shadowfax';
}
