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
     * @param string|null $statusCode
     * @param string|null $statusLabel
     * @param array $raw Full raw API response, kept for debugging/reconciliation.
     */
    public function __construct(
        public readonly ?string $statusCode,
        public readonly ?string $statusLabel,
        public readonly array $raw = []
    ) {
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
     * @return array
     */
    public function getRaw(): array
    {
        return $this->raw;
    }
}
