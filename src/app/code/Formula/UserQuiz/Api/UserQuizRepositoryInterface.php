<?php
/**
 * User Quiz Repository Interface
 */
declare(strict_types=1);

namespace Formula\UserQuiz\Api;

use Formula\UserQuiz\Api\Data\UserQuizInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface for managing user quiz data
 * @api
 */
interface UserQuizRepositoryInterface
{
    /**
     * Save User Quiz
     *
     * @param \Formula\UserQuiz\Api\Data\UserQuizInterface $userQuiz
     * @return \Formula\UserQuiz\Api\Data\UserQuizInterface
     * @throws CouldNotSaveException
     */
    public function save(\Formula\UserQuiz\Api\Data\UserQuizInterface $userQuiz);

    /**
     * Get User Quiz by ID
     *
     * @param int $entityId
     * @return \Formula\UserQuiz\Api\Data\UserQuizInterface
     * @throws NoSuchEntityException
     */
    public function getById($entityId);

    /**
     * Get User Quizzes by Customer ID
     *
     * @param int $customerId
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getByCustomerId($customerId);

    /**
     * Get list of User Quizzes
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete User Quiz
     *
     * @param \Formula\UserQuiz\Api\Data\UserQuizInterface $userQuiz
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(\Formula\UserQuiz\Api\Data\UserQuizInterface $userQuiz);

    /**
     * Delete User Quiz by ID
     *
     * @param int $entityId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($entityId);
}