<?php
/**
 * Formula Zoho Books Integration Module
 *
 * Pushes Magento order/invoice data one-way into Zoho Books for GST-compliant invoicing
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Formula_ZohoBooks',
    __DIR__
);
