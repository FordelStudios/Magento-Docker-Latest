<?php
/**
 * User Quiz Resource Model
 */
declare(strict_types=1);

namespace Formula\UserQuiz\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

class UserQuiz extends AbstractDb
{
    /**
     * @var DateTime
     */
    protected $date;

    /**
     * Constructor
     *
     * @param Context $context
     * @param DateTime $date
     * @param string|null $resourcePrefix
     */
    public function __construct(
        Context $context,
        DateTime $date,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
        $this->date = $date;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('formula_user_quiz', 'entity_id');
    }

    /**
     * Process save before
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $now = $this->date->gmtDate();
        
        if ($object->isObjectNew()) {
            $object->setCreatedAt($now);
        }
        
        $object->setUpdatedAt($now);
        
        return parent::_beforeSave($object);
    }
}