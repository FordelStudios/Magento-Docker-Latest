<?php
/**
 * User Quiz Model
 */
declare(strict_types=1);

namespace Formula\UserQuiz\Model;

use Formula\UserQuiz\Api\Data\UserQuizInterface;
use Magento\Framework\Model\AbstractModel;

class UserQuiz extends AbstractModel implements UserQuizInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\UserQuiz::class);
    }

    /**
     * @inheritdoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getQuestionId()
    {
        return $this->getData(self::QUESTION_ID);
    }

    /**
     * @inheritdoc
     */
    public function setQuestionId($questionId)
    {
        return $this->setData(self::QUESTION_ID, $questionId);
    }

    /**
     * @inheritdoc
     */
    public function getChosenOptionIds()
    {
        return $this->getData(self::CHOSEN_OPTION_IDS);
    }

    /**
     * @inheritdoc
     */
    public function setChosenOptionIds($chosenOptionIds)
    {
        return $this->setData(self::CHOSEN_OPTION_IDS, $chosenOptionIds);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
    
    /**
     * Process data before saving
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();
        
        // Ensure question_id is always saved as an integer
        if ($this->getQuestionId()) {
            $this->setQuestionId((int)$this->getQuestionId());
        }
        
        return $this;
    }

}