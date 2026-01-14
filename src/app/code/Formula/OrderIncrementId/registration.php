<?php
/**
 * Formula Order Increment ID Module
 * Generates custom order IDs in format: TFS-DDMMYY-NNNN
 */

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Formula_OrderIncrementId',
    __DIR__
);
