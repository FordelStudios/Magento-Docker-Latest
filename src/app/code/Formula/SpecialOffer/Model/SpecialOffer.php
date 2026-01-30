<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Model;

use Formula\SpecialOffer\Api\Data\SpecialOfferInterface;
use Formula\SpecialOffer\Model\ResourceModel\SpecialOffer as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class SpecialOffer extends AbstractModel implements SpecialOfferInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'formula_special_offer';

    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): ?int
    {
        $id = $this->getData(self::ENTITY_ID);
        return $id !== null ? (int)$id : null;
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId): self
    {
        return $this->setData(self::ENTITY_ID, (int)$entityId);
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): ?string
    {
        return $this->getData(self::TITLE);
    }

    /**
     * @inheritDoc
     */
    public function setTitle(string $title): SpecialOfferInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * @inheritDoc
     */
    public function getSubtitle(): ?string
    {
        return $this->getData(self::SUBTITLE);
    }

    /**
     * @inheritDoc
     */
    public function setSubtitle(?string $subtitle): SpecialOfferInterface
    {
        return $this->setData(self::SUBTITLE, $subtitle);
    }

    /**
     * @inheritDoc
     */
    public function getImage(): ?string
    {
        return $this->getData(self::IMAGE);
    }

    /**
     * @inheritDoc
     */
    public function setImage(string $image): SpecialOfferInterface
    {
        return $this->setData(self::IMAGE, $image);
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): ?string
    {
        return $this->getData(self::URL);
    }

    /**
     * @inheritDoc
     */
    public function setUrl(string $url): SpecialOfferInterface
    {
        return $this->setData(self::URL, $url);
    }

    /**
     * @inheritDoc
     */
    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritDoc
     */
    public function setIsActive(bool $isActive): SpecialOfferInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive ? 1 : 0);
    }

    /**
     * @inheritDoc
     */
    public function getStartDate(): ?string
    {
        return $this->getData(self::START_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setStartDate(?string $startDate): SpecialOfferInterface
    {
        return $this->setData(self::START_DATE, $startDate);
    }

    /**
     * @inheritDoc
     */
    public function getEndDate(): ?string
    {
        return $this->getData(self::END_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setEndDate(?string $endDate): SpecialOfferInterface
    {
        return $this->setData(self::END_DATE, $endDate);
    }

    /**
     * @inheritDoc
     */
    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    /**
     * @inheritDoc
     */
    public function setSortOrder(int $sortOrder): SpecialOfferInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }
}
