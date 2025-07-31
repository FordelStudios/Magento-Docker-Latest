<?php
namespace Formula\RefreshToken\Api\Data;

interface RefreshTokenInterface
{
    const ID = 'entity_id';
    const CUSTOMER_ID = 'customer_id';
    const TOKEN = 'token';
    const EXPIRES_AT = 'expires_at';

    public function getId();
    public function getCustomerId();
    public function getToken();
    public function getExpiresAt();

    public function setCustomerId($customerId);
    public function setToken($token);
    public function setExpiresAt($expiresAt);
}
