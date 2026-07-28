<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Model\Data;

/**
 * Typed result of a ShadowFax serviceability check.
 *
 * @todo Field names (serviceable/eta_days/estimated_delivery_date) are assumed;
 *       reconcile against real ShadowFax API docs when P1 credentials land.
 */
class ServiceabilityResult
{
    /**
     * @param bool $serviceable
     * @param int|null $etaDays
     * @param string|null $estDate
     * @param array $raw Full raw API response, kept for debugging/reconciliation.
     */
    public function __construct(
        public readonly bool $serviceable,
        public readonly ?int $etaDays,
        public readonly ?string $estDate,
        public readonly array $raw = []
    ) {
    }

    /**
     * @return bool
     */
    public function isServiceable(): bool
    {
        return $this->serviceable;
    }

    /**
     * @return int|null
     */
    public function getEtaDays(): ?int
    {
        return $this->etaDays;
    }

    /**
     * @return string|null
     */
    public function getEstDate(): ?string
    {
        return $this->estDate;
    }

    /**
     * @return array
     */
    public function getRaw(): array
    {
        return $this->raw;
    }
}
