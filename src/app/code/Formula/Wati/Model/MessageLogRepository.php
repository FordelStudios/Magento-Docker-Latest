<?php
declare(strict_types=1);

namespace Formula\Wati\Model;

use Formula\Wati\Api\Data\MessageLogInterface;
use Formula\Wati\Api\Data\MessageLogInterfaceFactory;
use Formula\Wati\Api\MessageLogRepositoryInterface;
use Formula\Wati\Model\ResourceModel\MessageLog as MessageLogResource;
use Formula\Wati\Model\ResourceModel\MessageLog\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Wati message log repository
 */
class MessageLogRepository implements MessageLogRepositoryInterface
{
    /**
     * @var MessageLogResource
     */
    protected $resource;

    /**
     * @var MessageLogInterfaceFactory
     */
    protected $messageLogFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param MessageLogResource $resource
     * @param MessageLogInterfaceFactory $messageLogFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        MessageLogResource $resource,
        MessageLogInterfaceFactory $messageLogFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->messageLogFactory = $messageLogFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(MessageLogInterface $messageLog)
    {
        try {
            $this->resource->save($messageLog);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the message log: %1',
                $exception->getMessage()
            ));
        }
        return $messageLog;
    }

    /**
     * @inheritdoc
     */
    public function getById($logId)
    {
        $messageLog = $this->messageLogFactory->create();
        $this->resource->load($messageLog, $logId);
        if (!$messageLog->getId()) {
            throw new NoSuchEntityException(__('Message log with id "%1" does not exist.', $logId));
        }
        return $messageLog;
    }

    /**
     * @inheritdoc
     */
    public function getByMessageId($messageId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('message_id', $messageId);
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();
        if ($item && $item->getId()) {
            return $item;
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function delete(MessageLogInterface $messageLog)
    {
        try {
            $this->resource->delete($messageLog);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the message log: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($logId)
    {
        return $this->delete($this->getById($logId));
    }
}
