<?php
namespace Formula\Blog\Controller\Adminhtml\Blog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\Blog\Model\BlogFactory;
use Formula\Blog\Model\BlogRepository;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends Action
{
    protected $dataPersistor;
    protected $blogFactory;
    protected $blogRepository;
    protected $logger;
    protected $uploaderFactory;
    protected $filesystem;

    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        BlogFactory $blogFactory,
        BlogRepository $blogRepository,
        LoggerInterface $logger,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->blogFactory = $blogFactory;
        $this->blogRepository = $blogRepository;
        $this->logger = $logger;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Blog::blog');
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        $this->logger->debug('Blog Save Controller: Post data', ['data' => $data]);
        
        if ($data) {
            // Handle checkbox value
            if (isset($data['is_published']) && $data['is_published'] === 'true') {
                $data['is_published'] = 1;
            }
            
            $id = isset($data['blog_id']) ? $data['blog_id'] : null;
            
            if (empty($id)) {
                $data['blog_id'] = null;
                $model = $this->blogFactory->create();
            } else {
                try {
                    $model = $this->blogRepository->getById($id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This blog no longer exists.'));
                    $this->logger->critical('Blog save error: ' . $e->getMessage());
                    return $resultRedirect->setPath('*/*/');
                }
            }
            
            // Handle image upload
            if (isset($_FILES['image']) && isset($_FILES['image']['name']) && strlen($_FILES['image']['name'])) {
                try {
                    $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                    $target = $mediaDir->getAbsolutePath('formula_blog/image/');
                    
                    // Create target directory if it doesn't exist
                    if (!$mediaDir->isExist('formula_blog/image')) {
                        $mediaDir->create('formula_blog/image');
                    }
                    
                    $uploader = $this->uploaderFactory->create(['fileId' => 'image']);
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
                    $uploader->setAllowRenameFiles(true);
                    $result = $uploader->save($target);
                    
                    if ($result['file']) {
                        $data['image'] = $result['file'];
                    }
                } catch (\Exception $e) {
                    $this->logger->critical('Image upload error: ' . $e->getMessage());
                    $this->messageManager->addErrorMessage(__('Error uploading image: %1', $e->getMessage()));
                }
            } elseif (isset($data['image']) && is_array($data['image'])) {
                if (isset($data['image'][0]['name']) && !empty($data['image'][0]['name'])) {
                    $data['image'] = $data['image'][0]['name'];
                } else {
                    $data['image'] = null;
                }
            }
            
            // Handle product_ids if it's an array
            if (isset($data['product_ids']) && is_array($data['product_ids'])) {
                $data['product_ids'] = implode(',', $data['product_ids']);
            }
            
            $model->setData($data);

            try {
                $this->blogRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the blog post.'));
                $this->dataPersistor->clear('blog');
                
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['blog_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->critical('Blog save error: ' . $e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the blog post.'));
                $this->logger->critical('Blog save error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            }
            
            $this->dataPersistor->set('blog', $data);
            return $resultRedirect->setPath('*/*/edit', ['blog_id' => $id]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}