<?php
declare(strict_types=1);

namespace Formula\HomeContent\Api;

use Formula\HomeContent\Api\Data\HomeContentInterface;

interface HomeContentRepositoryInterface
{
    public function save(HomeContentInterface $homeContent);

    public function getById($entityId);

    public function delete(HomeContentInterface $homeContent);

    public function deleteById($entityId);

    public function getList();
}