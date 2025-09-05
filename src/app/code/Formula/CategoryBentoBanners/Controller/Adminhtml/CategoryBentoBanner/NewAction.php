<?php
namespace Formula\CategoryBentoBanners\Controller\Adminhtml\CategoryBentoBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class NewAction extends Action
{
    const ADMIN_RESOURCE = 'Formula_CategoryBentoBanners::save';

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
        $resultPage->addBreadcrumb(__('New Bento Banner'), __('New Bento Banner'));
        $resultPage->getConfig()->getTitle()->prepend(__('New Category Bento Banner'));

        return $resultPage;
    }
}