<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Model\Data;

/**
 * Typed result of a ShadowFax track-shipment call.
 *
 * @todo Field names (status_code/status_label) are assumed; reconcile against real
 *       ShadowFax API docs when P1 credentials land.
 */
class TrackingResult
{
    /**
     * @var string|null
     */
    private $statusCode;

    /**
     * @var string|null
     */
    private $statusLabel;

    /**
     * @var array
     */
    private $raw;

    /**
     * @param string|null $statusCode
     * @param string|null $statusLabel
     * @param array $raw
     */
    public function __construct(?string $statusCode, ?string $statusLabel, array $raw = [])
    {
        $this->statusCode = $statusCode;
        $this->statusLabel = $statusLabel;
        $this->raw = $raw;
    }

    /**
     * @return string|null
     */
    public function getStatusCode(): ?string
    {
        return $this->statusCode;
    }

    /**
     * @return string|null
     */
    public function getStatusLabel(): ?string
    {
        return $this->statusLabel;
    }

    /**
     * Full raw API response, kept for debugging/reconciliation.
     *
     * @return array
     */
    public function getRaw(): array
    {
        return $this->raw;
    }
}
