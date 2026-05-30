<?php
declare(strict_types=1);

namespace Formula\Announcement\Api;

use Formula\Announcement\Api\Data\AnnouncementInterface;

/**
 * Public service that exposes the storefront announcement bar content,
 * editable in admin under Stores > Configuration > General > Announcement Bar.
 */
interface AnnouncementManagementInterface
{
    /**
     * @return \Formula\Announcement\Api\Data\AnnouncementInterface
     */
    public function getAnnouncement(): AnnouncementInterface;
}
