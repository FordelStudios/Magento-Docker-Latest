<?php
// app/code/Formula/CategoryBanners/Controller/Adminhtml/CategoryBanner/Index.php
namespace Formula\CategoryBanners\Controller\Adminhtml\CategoryBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Formula_CategoryBanners::manage';

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
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Formula_CategoryBanners::manage');
        $resultPage->addBreadcrumb(__('Formula'), __('Formula'));
        $resultPage->addBreadcrumb(__('Category Banners'), __('Category Banners'));
        $resultPage->getConfig()->getTitle()->prepend(__('Category Banners'));

        return $resultPage;
    }
}