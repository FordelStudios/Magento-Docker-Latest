<?php
namespace Formula\Reel\Controller\Adminhtml\Reel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class GetFileSize extends Action
{
    protected $resultJsonFactory;
    protected $filesystem;
    protected $mediaDirectory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Reel::reel');
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $fileName = $this->getRequest()->getParam('filename');
        
        if (!$fileName) {
            return $result->setData(['success' => false]);
        }
        
        $filePath = $this->mediaDirectory->getAbsolutePath('reel/video/' . $fileName);
        
        if (file_exists($filePath)) {
            $size = filesize($filePath);
            return $result->setData([
                'success' => true,
                'size' => $size
            ]);
        }
        
        return $result->setData(['success' => false]);
    }
}