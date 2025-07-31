<?php
namespace Formula\Blog\Controller\Adminhtml\Blog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Formula\Blog\Model\BlogRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Edit extends Action
{
    protected $resultPageFactory;
    protected $blogRepository;
    protected $logger;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        BlogRepository $blogRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->blogRepository = $blogRepository;
        $this->logger = $logger;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Blog::blog');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('blog_id');
        
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Formula_Blog::blog')
            ->addBreadcrumb(__('Blogs'), __('Blogs'))
            ->addBreadcrumb(__('Manage Blogs'), __('Manage Blogs'));

        if ($id) {
            try {
                $blog = $this->blogRepository->getById($id);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Blog: %1', $blog->getTitle()));
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This blog no longer exists.'));
                $this->logger->critical('Blog edit error: ' . $e->getMessage());
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred while loading the blog.'));
                $this->logger->critical('Blog edit error: ' . $e->getMessage());
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Blog'));
        }
        
        return $resultPage;
    }
}