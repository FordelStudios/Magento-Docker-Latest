<?php
declare(strict_types=1);

namespace Formula\Announcement\Api\Data;

/**
 * Announcement bar payload returned to the storefront.
 */
interface AnnouncementInterface
{
    public const ENABLED = 'enabled';
    public const TEXT = 'text';

    /**
     * @return bool
     */
    public function getEnabled(): bool;

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled): self;

    /**
     * @return string
     */
    public function getText(): string;

    /**
     * @param string $text
     * @return $this
     */
    public function setText(string $text): self;
}
