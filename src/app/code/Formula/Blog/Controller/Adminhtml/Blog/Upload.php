<?php
namespace Formula\Blog\Controller\Adminhtml\Blog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Upload extends Action
{
    protected $uploaderFactory;
    protected $filesystem;

    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Blog::blog');
    }

    public function execute()
    {
        $result = [];
        
        try {
            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $target = $mediaDir->getAbsolutePath('formula_blog/tmp/image/');
            
            // Create target directory if it doesn't exist
            if (!$mediaDir->isExist('formula_blog/tmp/image')) {
                $mediaDir->create('formula_blog/tmp/image');
            }
            
            $uploader = $this->uploaderFactory->create(['fileId' => 'image']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
            $uploader->setAllowRenameFiles(true);
            $result = $uploader->save($target);
            
            if ($result['file']) {
                $result['url'] = $this->_objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)
                    ->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'formula_blog/tmp/image/' . $result['file'];
                $result['name'] = $result['file'];
            }
            
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}