<?php
/**
 * User Quiz Collection
 */
declare(strict_types=1);

namespace Formula\UserQuiz\Model\ResourceModel\UserQuiz;

use Formula\UserQuiz\Model\UserQuiz;
use Formula\UserQuiz\Model\ResourceModel\UserQuiz as UserQuizResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            UserQuiz::class,
            UserQuizResource::class
        );
    }
    
    /**
     * Add customer filter
     *
     * @param int $customerId
     * @return $this
     */
    public function addCustomerFilter($customerId)
    {
        $this->addFieldToFilter('customer_id', (int)$customerId);
        return $this;
    }
    
    /**
     * Add question filter
     *
     * @param int $questionId
     * @return $this
     */
    public function addQuestionFilter($questionId)
    {
        $this->addFieldToFilter('question_id', (int)$questionId);
        return $this;
    }
}