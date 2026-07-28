<?php
/**
 * Formula Delivery Partner Abstraction
 *
 * Neutral delivery-partner contract so order/payment modules can route
 * shipments to any courier (Shiprocket, ShadowFax, ...) without depending
 * on a specific courier module. Purely additive — no existing module's
 * business logic is touched by this module.
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Formula_DeliveryPartner',
    __DIR__
);
