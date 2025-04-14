<?php
namespace Formula\Review\Plugin;

use Formula\Review\Api\Data\ReviewInterface;
use Magento\Framework\Reflection\DataObjectProcessor;

class DataObjectProcessorPlugin
{
    /**
     * Plugin to handle array data types for the API serialization
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
        // Check if we're processing a review object
        if ($dataObject instanceof ReviewInterface && !empty($dataObject->getRatingsDetails())) {
            $result['ratings_details'] = $dataObject->getRatingsDetails();
        }

        // Handle images array
        if (!empty($dataObject->getImages())) {
            $result['images'] = $dataObject->getImages();
        }
            
        // Make sure is_recommended is included even if false
        if ($dataObject->getIsRecommended() !== null) {
            $result['is_recommended'] = (bool)$dataObject->getIsRecommended();
        }            
        
        return $result;
    }
}