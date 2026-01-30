<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Api\Data;

interface SpecialOfferInterface
{
    public const ENTITY_ID = 'entity_id';
    public const TITLE = 'title';
    public const SUBTITLE = 'subtitle';
    public const IMAGE = 'image';
    public const URL = 'url';
    public const IS_ACTIVE = 'is_active';
    public const START_DATE = 'start_date';
    public const END_DATE = 'end_date';
    public const SORT_ORDER = 'sort_order';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * @param int $entityId
     * @return $this
     */
    public function setEntityId(int $entityId): self;

    /**
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self;

    /**
     * @return string|null
     */
    public function getSubtitle(): ?string;

    /**
     * @param string|null $subtitle
     * @return $this
     */
    public function setSubtitle(?string $subtitle): self;

    /**
     * @return string|null
     */
    public function getImage(): ?string;

    /**
     * @param string $image
     * @return $this
     */
    public function setImage(string $image): self;

    /**
     * @return string|null
     */
    public function getUrl(): ?string;

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self;

    /**
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self;

    /**
     * @return string|null
     */
    public function getStartDate(): ?string;

    /**
     * @param string|null $startDate
     * @return $this
     */
    public function setStartDate(?string $startDate): self;

    /**
     * @return string|null
     */
    public function getEndDate(): ?string;

    /**
     * @param string|null $endDate
     * @return $this
     */
    public function setEndDate(?string $endDate): self;

    /**
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): self;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
