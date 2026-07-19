<?php
namespace Formula\ProductFaq\Model\Data;

use Formula\ProductFaq\Api\Data\FaqInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class Faq extends AbstractExtensibleObject implements FaqInterface
{
    /**
     * @return string
     */
    public function getQuestion()
    {
        return $this->_get(self::QUESTION);
    }

    /**
     * @param string $question
     * @return $this
     */
    public function setQuestion($question)
    {
        return $this->setData(self::QUESTION, $question);
    }

    /**
     * @return string
     */
    public function getAnswer()
    {
        return $this->_get(self::ANSWER);
    }

    /**
     * @param string $answer
     * @return $this
     */
    public function setAnswer($answer)
    {
        return $this->setData(self::ANSWER, $answer);
    }
}
