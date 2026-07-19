<?php
namespace Formula\ProductFaq\Api\Data;

interface FaqInterface
{
    const QUESTION = 'question';
    const ANSWER = 'answer';

    /**
     * @return string
     */
    public function getQuestion();

    /**
     * @param string $question
     * @return $this
     */
    public function setQuestion($question);

    /**
     * @return string
     */
    public function getAnswer();

    /**
     * @param string $answer
     * @return $this
     */
    public function setAnswer($answer);
}
