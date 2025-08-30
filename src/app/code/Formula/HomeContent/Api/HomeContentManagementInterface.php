<?php
declare(strict_types=1);

namespace Formula\HomeContent\Api;

interface HomeContentManagementInterface
{
    /**
     * Retrieve home content
     *
     * @return \Formula\HomeContent\Api\Data\HomeContentResponseInterface
     */
    public function getHomeContent();

    /**
     * Get all home content entities with all fields
     *
     * @return \Formula\HomeContent\Api\Data\HomeContentInterface[]
     */
    public function getAllHomeContentEntities();
}