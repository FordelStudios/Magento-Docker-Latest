<?php
declare(strict_types=1);

namespace Formula\HomeContent\Model;

use Formula\HomeContent\Api\Data\HomeContentInterface;
use Formula\HomeContent\Api\HomeContentRepositoryInterface;
use Formula\HomeContent\Model\HomeContentFactory;
use Formula\HomeContent\Model\ResourceModel\HomeContent as HomeContentResource;
use Formula\HomeContent\Model\ResourceModel\HomeContent\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class HomeContentRepository implements HomeContentRepositoryInterface
{
    protected $resource;
    protected $homeContentFactory;
    protected $collectionFactory;

    public function __construct(
        HomeContentResource $resource,
        HomeContentFactory $homeContentFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->homeContentFactory = $homeContentFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function save(HomeContentInterface $homeContent)
    {
        try {
            $this->resource->save($homeContent);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $homeContent;
    }

    public function getById($entityId)
    {
        $homeContent = $this->homeContentFactory->create();
        $this->resource->load($homeContent, $entityId);
        if (!$homeContent->getEntityId()) {
            throw new NoSuchEntityException(__('HomeContent with id "%1" does not exist.', $entityId));
        }
        return $homeContent;
    }

    public function delete(HomeContentInterface $homeContent)
    {
        try {
            $this->resource->delete($homeContent);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    public function deleteById($entityId)
    {
        return $this->delete($this->getById($entityId));
    }

    public function getList()
    {
        $collection = $this->collectionFactory->create();
        return $collection;
    }
}