<?php
namespace Formula\CategoryBentoBanners\Controller\Adminhtml\CategoryBentoBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Formula_CategoryBentoBanners::category_bento_banners';

    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Formula_CategoryBentoBanners::category_bento_banners');
        $resultPage->addBreadcrumb(__('Category Bento Banners'), __('Category Bento Banners'));
        $resultPage->addBreadcrumb(__('Manage Bento Banners'), __('Manage Bento Banners'));
        $resultPage->getConfig()->getTitle()->prepend(__('Category Bento Banners'));

        return $resultPage;
    }
}