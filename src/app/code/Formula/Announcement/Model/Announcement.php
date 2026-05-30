<?php
declare(strict_types=1);

namespace Formula\Announcement\Model;

use Formula\Announcement\Api\Data\AnnouncementInterface;
use Magento\Framework\DataObject;

class Announcement extends DataObject implements AnnouncementInterface
{
    public function getEnabled(): bool
    {
        return (bool)$this->getData(self::ENABLED);
    }

    public function setEnabled(bool $enabled): AnnouncementInterface
    {
        return $this->setData(self::ENABLED, $enabled);
    }

    public function getText(): string
    {
        return (string)$this->getData(self::TEXT);
    }

    public function setText(string $text): AnnouncementInterface
    {
        return $this->setData(self::TEXT, $text);
    }
}
