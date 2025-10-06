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
use Formula\Blog\Model\ImageUploader;
use Magento\Framework\Serialize\Serializer\Json;

class Save extends Action
{
    protected $dataPersistor;
    protected $blogFactory;
    protected $blogRepository;
    protected $logger;
    protected $uploaderFactory;
    protected $filesystem;
    protected $imageUploader; 
    protected $jsonSerializer;   

    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        BlogFactory $blogFactory,
        BlogRepository $blogRepository,
        LoggerInterface $logger,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        ImageUploader $imageUploader,
        Json $jsonSerializer
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->blogFactory = $blogFactory;
        $this->blogRepository = $blogRepository;
        $this->logger = $logger;
        $this->uploaderFactory = $uploaderFactory;
        $this->imageUploader = $imageUploader;
        $this->filesystem = $filesystem;
        $this->jsonSerializer = $jsonSerializer;
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
        
        // Debug category_ids specifically
        if (isset($data['category_ids'])) {
            $this->logger->debug('Blog Save Controller: category_ids before processing', [
                'category_ids' => $data['category_ids'],
                'type' => gettype($data['category_ids']),
                'is_array' => is_array($data['category_ids'])
            ]);
        }
        
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
            if (isset($data['image']) && is_array($data['image'])) {
                    if (!empty($data['image'][0]['name']) && !empty($data['image'][0]['tmp_name'])) {
                        try {
                            $this->imageUploader->setBaseTmpPath("blog/tmp/image");
                            $this->imageUploader->setBasePath("blog/image");
                            $data['image'] = $data['image'][0]['name'];
                            $this->imageUploader->moveFileFromTmp($data['image']);
                        } catch (\Exception $e) {
                            $this->logger->critical($e);
                            $this->messageManager->addExceptionMessage($e, __('Error processing image upload: %1', $e->getMessage()));
                        }
                    } elseif (!empty($data['image'][0]['name']) && empty($data['image'][0]['tmp_name'])) {
                        $data['image'] = $data['image'][0]['name'];
                    } else {
                        unset($data['image']);
                    }
            }

            // Handle product_ids if it's an array
            if (isset($data['product_ids']) && is_array($data['product_ids'])) {
                $data['product_ids'] = implode(',', $data['product_ids']);
            }
            
            // Handle category_ids if it's an array
            if (isset($data['category_ids']) && is_array($data['category_ids'])) {
                $data['category_ids'] = $this->jsonSerializer->serialize($data['category_ids']);
            } elseif (!isset($data['category_ids']) || empty($data['category_ids'])) {
                $data['category_ids'] = $this->jsonSerializer->serialize([]);
            }
            
            // Debug category_ids after processing
            if (isset($data['category_ids'])) {
                $this->logger->debug('Blog Save Controller: category_ids after processing', [
                    'category_ids' => $data['category_ids'],
                    'type' => gettype($data['category_ids'])
                ]);
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