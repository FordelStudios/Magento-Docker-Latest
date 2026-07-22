<?php
namespace Formula\ProductFaq\Plugin;

use Formula\ProductFaq\Api\Data\FaqInterface;
use Magento\Framework\Reflection\DataObjectProcessor;

class DataObjectProcessorPlugin
{
    /**
     * Plugin to ensure FAQ fields are included in the API serialization
     *
     * @param DataObjectProcessor $subject
     * @param array $result
     * @param mixed $dataObject
     * @param string $dataObjectType
     * @return array
     */
    public function afterBuildOutputDataArray(
        DataObjectProcessor $subject,
        array $result,
        $dataObject,
        $dataObjectType
    ) {
        // Only process Faq objects, not other data objects like SearchCriteria
        if ($dataObject instanceof FaqInterface) {
            if ($dataObject->getQuestion() !== null) {
                $result[FaqInterface::QUESTION] = $dataObject->getQuestion();
            }

            if ($dataObject->getAnswer() !== null) {
                $result[FaqInterface::ANSWER] = $dataObject->getAnswer();
            }
        }

        return $result;
    }
}
