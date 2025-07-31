<?php
// src/app/code/Formula/Blog/Controller/Adminhtml/Blog/Delete.php
namespace Formula\Blog\Controller\Adminhtml\Blog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\Blog\Model\BlogRepository;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
{
    protected $blogRepository;

    public function __construct(
        Context $context,
        BlogRepository $blogRepository
    ) {
        $this->blogRepository = $blogRepository;
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Blog::blog');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('blog_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        
        if ($id) {
            try {
                $this->blogRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The blog post has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred while deleting the blog post.'));
            }
            return $resultRedirect->setPath('*/*/edit', ['blog_id' => $id]);
        }
        
        $this->messageManager->addErrorMessage(__('We can\'t find a blog post to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}