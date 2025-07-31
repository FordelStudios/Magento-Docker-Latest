<?php
// src/app/code/Formula/Reel/Controller/Adminhtml/Reel/Index.php
namespace Formula\Reel\Controller\Adminhtml\Reel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Reel::reel');
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Formula_Reel::reel');
        $resultPage->addBreadcrumb(__('Reels'), __('Reels'));
        $resultPage->addBreadcrumb(__('Manage Reels'), __('Manage Reels'));
        $resultPage->getConfig()->getTitle()->prepend(__('Reels'));

        return $resultPage;
    }
}