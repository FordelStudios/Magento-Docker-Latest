<?php
declare(strict_types=1);

namespace Formula\HomeContent\Controller\Adminhtml\HomeContent;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Formula\HomeContent\Model\ImageUploader;

class UploadIngredientImage extends Action
{
    protected $imageUploader;

    public function __construct(
        Context $context,
        ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_HomeContent::home_content_manage');
    }

    public function execute()
    {
        try {
            // Debug: Log the request data
            $request = $this->getRequest();
            $files = $request->getFiles();
            $params = $request->getParams();
            
            // Log to file for debugging
            $logFile = BP . '/var/log/ingredient_upload_debug.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - UploadIngredientImage executed\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Files: " . json_encode($files) . "\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Params: " . json_encode($params) . "\n", FILE_APPEND);
            
            // Handle dynamic rows file structure
            $fileId = 'discover_korean_ingredients_banners';
            if (isset($files[$fileId]) && is_array($files[$fileId])) {
                // Find the first row with image data
                foreach ($files[$fileId] as $rowIndex => $rowData) {
                    if (isset($rowData['image']) && is_array($rowData['image'])) {
                        // Create a temporary file structure that the uploader can handle
                        $_FILES['image'] = $rowData['image'];
                        break;
                    }
                }
            }
            
            $result = $this->imageUploader->saveFileToTmpDir('image');
            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
            
            // Log error to file
            $logFile = BP . '/var/log/ingredient_upload_debug.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
