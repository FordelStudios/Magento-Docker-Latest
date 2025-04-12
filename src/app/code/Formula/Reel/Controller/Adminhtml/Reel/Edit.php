<?php
namespace Formula\Reel\Controller\Adminhtml\Reel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Formula\Reel\Model\ReelRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class Edit extends Action
{
    protected $resultPageFactory;
    protected $reelRepository;
    protected $logger;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ReelRepository $reelRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->reelRepository = $reelRepository;
        $this->logger = $logger;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Reel::reel');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('reel_id');
        
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Formula_Reel::reel')
            ->addBreadcrumb(__('Reels'), __('Reels'))
            ->addBreadcrumb(__('Manage Reels'), __('Manage Reels'));

        if ($id) {
            try {
                $reel = $this->reelRepository->getById($id);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Reel: %1', $reel->getDescription()));
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This reel no longer exists.'));
                $this->logger->critical('Reel edit error: ' . $e->getMessage());
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred while loading the reel.'));
                $this->logger->critical('Reel edit error: ' . $e->getMessage());
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Reel'));
        }
        
        return $resultPage;
    }
}