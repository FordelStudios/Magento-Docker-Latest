<?php
namespace Formula\RefreshToken\Api;

interface RefreshTokenRepositoryInterface
{
    /**
     * Generate a new customer token using a refresh token
     *
     * @param string $refreshToken
     * @return string[]
     */
    public function refresh($refreshToken);
}