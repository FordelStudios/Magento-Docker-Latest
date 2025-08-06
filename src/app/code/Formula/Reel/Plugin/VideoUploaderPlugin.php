<?php
namespace Formula\Reel\Plugin;

class VideoUploaderPlugin
{
    /**
     * Prevent MediaGalleryIntegration from processing video files
     * 
     * @param \Magento\MediaGalleryIntegration\Plugin\SaveImageInformation $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\File\Uploader $uploader
     * @param mixed $result
     * @param string $path
     * @return mixed
     */
    public function aroundAfterSave(
        \Magento\MediaGalleryIntegration\Plugin\SaveImageInformation $subject,
        \Closure $proceed,
        \Magento\Framework\File\Uploader $uploader,
        $result,
        $path
    ) {
        // If result is false, create a valid array return instead
        if ($result === false) {
            return [
                'file' => basename($path),
                'path' => dirname($path),
                'type' => 'video/mp4' // Default type, can be modified if needed
            ];
        }
        
        // If this is a video file, skip the MediaGalleryIntegration processing
        if (is_array($result) && isset($result['file'])) {
            $fileExt = pathinfo($result['file'], PATHINFO_EXTENSION);
            if (in_array(strtolower($fileExt), ['mp4', 'mkv', 'gif'])) {
                return $result;
            }
        }
        
        // For other files, proceed with the original plugin
        return $proceed($uploader, $result, $path);
    }
}