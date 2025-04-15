<?php
/**
 * User Quiz Interface
 */
declare(strict_types=1);

namespace Formula\UserQuiz\Api\Data;

/**
 * User Quiz interface for managing quiz responses
 * @api
 */
interface UserQuizInterface
{
    /**
     * Constants for keys of data array.
     */
    const ENTITY_ID = 'entity_id';
    const CUSTOMER_ID = 'customer_id';
    const QUESTION_ID = 'question_id';
    const CHOSEN_OPTION_IDS = 'chosen_option_ids';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Get Entity ID
     *
     * @return int|null
     */
    public function getEntityId();

    /**
     * Set Entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * Get Customer ID
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set Customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Get Question ID
     *
     * @return int
     */
    public function getQuestionId();

    /**
     * Set Question ID
     *
     * @param int $questionId
     * @return $this
     */
    public function setQuestionId($questionId);

    /**
     * Get Chosen Option IDs
     *
     * @return string|null
     */
    public function getChosenOptionIds();

    /**
     * Set Chosen Option IDs
     *
     * @param string $chosenOptionIds
     * @return $this
     */
    public function setChosenOptionIds($chosenOptionIds);

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get Updated At
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set Updated At
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);
}