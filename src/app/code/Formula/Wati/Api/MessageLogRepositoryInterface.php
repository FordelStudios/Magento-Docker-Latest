<?php
declare(strict_types=1);

namespace Formula\Wati\Api;

use Formula\Wati\Api\Data\MessageLogInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface for Wati message log repository
 */
interface MessageLogRepositoryInterface
{
    /**
     * Save message log
     *
     * @param MessageLogInterface $messageLog
     * @return MessageLogInterface
     * @throws LocalizedException
     */
    public function save(MessageLogInterface $messageLog);

    /**
     * Get message log by ID
     *
     * @param int $logId
     * @return MessageLogInterface
     * @throws NoSuchEntityException
     */
    public function getById($logId);

    /**
     * Get message log by Wati message ID
     *
     * @param string $messageId
     * @return MessageLogInterface|null
     */
    public function getByMessageId($messageId);

    /**
     * Delete message log
     *
     * @param MessageLogInterface $messageLog
     * @return bool
     * @throws LocalizedException
     */
    public function delete(MessageLogInterface $messageLog);

    /**
     * Delete message log by ID
     *
     * @param int $logId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($logId);
}
