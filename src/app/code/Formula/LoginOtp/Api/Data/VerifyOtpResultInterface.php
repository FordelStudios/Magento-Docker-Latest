<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Api\Data;

interface VerifyOtpResultInterface
{
    /**
     * @return string
     */
    public function getAccessToken();

    /**
     * @param string $accessToken
     * @return $this
     */
    public function setAccessToken($accessToken);

    /**
     * @return string|null
     */
    public function getRefreshToken();

    /**
     * @param string|null $refreshToken
     * @return $this
     */
    public function setRefreshToken($refreshToken);

    /**
     * Whether the customer was created as part of this verify call.
     * Frontend can use this to route new vs returning users (e.g. show a
     * "welcome" beat for first-timers).
     * @return bool
     */
    public function getIsNewUser();

    /**
     * @param bool $isNewUser
     * @return $this
     */
    public function setIsNewUser($isNewUser);

    /**
     * Customer ID (so frontend can correlate with subsequent calls without
     * having to decode the JWT).
     * @return int
     */
    public function getCustomerId();

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * True if the customer is still on a placeholder `<phone>@formula.placeholder`
     * email (no real email captured yet). Frontend uses this to prompt for
     * email at first checkout (Phase 2 behavior).
     * @return bool
     */
    public function getHasPlaceholderEmail();

    /**
     * @param bool $hasPlaceholderEmail
     * @return $this
     */
    public function setHasPlaceholderEmail($hasPlaceholderEmail);
}
