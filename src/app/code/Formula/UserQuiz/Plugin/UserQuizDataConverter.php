<?php
/**
 * User Quiz Data Converter Plugin
 */
declare(strict_types=1);

namespace Formula\UserQuiz\Plugin;

use Formula\UserQuiz\Api\Data\UserQuizInterface;
use Formula\UserQuiz\Api\UserQuizRepositoryInterface;
use Formula\UserQuiz\Model\UserQuizFactory;
use Magento\Framework\Webapi\Rest\Request;

class UserQuizDataConverter
{
    /**
     * @var UserQuizFactory
     */
    private $userQuizFactory;
    
    /**
     * @var Request
     */
    private $request;

    /**
     * @param UserQuizFactory $userQuizFactory
     * @param Request $request
     */
    public function __construct(
        UserQuizFactory $userQuizFactory,
        Request $request
    ) {
        $this->userQuizFactory = $userQuizFactory;
        $this->request = $request;
    }

    /**
     * Convert JSON body data to UserQuiz model
     *
     * @param UserQuizRepositoryInterface $subject
     * @param UserQuizInterface $userQuiz
     * @return array
     */
    public function beforeSave(
        UserQuizRepositoryInterface $subject,
        UserQuizInterface $userQuiz
    ) {
        if ($this->request->isPost() || $this->request->isPut()) {
            $requestData = $this->request->getBodyParams();
            
            // If it's a raw JSON body (not already processed as a UserQuiz object)
            if (is_array($requestData) && !$userQuiz->getQuestionId()) {
                foreach ($requestData as $key => $value) {
                    $setter = 'set' . str_replace('_', '', ucwords($key, '_'));
                    if (method_exists($userQuiz, $setter)) {
                        $userQuiz->$setter($value);
                    }
                }
            }
        }
        
        return [$userQuiz];
    }
}