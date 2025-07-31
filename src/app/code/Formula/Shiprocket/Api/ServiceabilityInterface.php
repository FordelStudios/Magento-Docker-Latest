<?php
/**
 * Serviceability Interface
 * File: src/app/code/Formula/Shiprocket/Api/ServiceabilityInterface.php
 */
namespace Formula\Shiprocket\Api;

interface ServiceabilityInterface
{
    /**
     * Check courier serviceability
     *
     * @param string $pincode
     * @param bool $cod
     * @param float $weight
     * @return mixed
     */
    public function checkServiceability($pincode, $cod, $weight);
}