<?php
declare(strict_types=1);

namespace Formula\HomeContent\Controller\Adminhtml\HomeContent;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Formula\HomeContent\Api\HomeContentRepositoryInterface;
use Psr\Log\LoggerInterface;

class Delete extends Action implements HttpPostActionInterface
{
    protected $homeContentRepository;
    protected $logger;

    public function __construct(
        Context $context,
        HomeContentRepositoryInterface $homeContentRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->homeContentRepository = $homeContentRepository;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');

        if ($id) {
            try {
                $homeContent = $this->homeContentRepository->getById($id);
                $this->homeContentRepository->delete($homeContent);
                $this->messageManager->addSuccessMessage(__('The home content has been deleted.'));
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                $this->messageManager->addErrorMessage(__('Something went wrong while deleting the home content.'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a home content to delete.'));
        }

        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_HomeContent::home_content_manage');
    }
}