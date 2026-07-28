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
     * @var bool
     */
    private $serviceable;

    /**
     * @var int|null
     */
    private $etaDays;

    /**
     * @var string|null
     */
    private $estDate;

    /**
     * @var array
     */
    private $raw;

    /**
     * @param bool $serviceable
     * @param int|null $etaDays
     * @param string|null $estDate
     * @param array $raw
     */
    public function __construct(bool $serviceable, ?int $etaDays, ?string $estDate, array $raw = [])
    {
        $this->serviceable = $serviceable;
        $this->etaDays = $etaDays;
        $this->estDate = $estDate;
        $this->raw = $raw;
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
     * Full raw API response, kept for debugging/reconciliation.
     *
     * @return array
     */
    public function getRaw(): array
    {
        return $this->raw;
    }
}
