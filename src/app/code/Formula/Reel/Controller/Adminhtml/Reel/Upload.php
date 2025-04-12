<?php
namespace Formula\Reel\Controller\Adminhtml\Reel;

use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class Upload extends \Magento\Backend\App\Action
{
    protected $videoUploader;
    protected $logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Formula\Reel\Model\VideoUploader $videoUploader,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->videoUploader = $videoUploader;
        $this->logger = $logger;
    }

    public function execute()
    {
        $this->logger->debug('Upload controller executed');
        $logFile = BP . '/var/log/upload_controller.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Upload controller executed\n", FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - FILES: " . json_encode($_FILES) . "\n", FILE_APPEND);
    
        try {
            $fileId = $this->getRequest()->getParam('param_name', 'video');
            $this->logger->debug('Attempting to upload file with ID: ' . $fileId);
            
            // Set allowed extensions
            $this->videoUploader->setAllowedExtensions(['mp4', 'mkv', 'gif']);
            
            $result = $this->videoUploader->saveFileToTmpDir($fileId);
            $this->logger->debug('File uploaded to temp directory', $result);
            
            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
            $this->logger->critical('Error in Upload controller: ' . $e->getMessage());
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

          file_put_contents($logFile, date('Y-m-d H:i:s') . " - Result: " . json_encode($result) . "\n", FILE_APPEND);
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}