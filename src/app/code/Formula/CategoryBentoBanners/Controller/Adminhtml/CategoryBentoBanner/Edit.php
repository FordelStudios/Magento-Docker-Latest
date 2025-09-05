<?php
namespace Formula\CategoryBentoBanners\Controller\Adminhtml\CategoryBentoBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Formula\CategoryBentoBanners\Api\CategoryBentoBannerRepositoryInterface;

class Edit extends Action
{
    const ADMIN_RESOURCE = 'Formula_CategoryBentoBanners::save';

    protected $resultPageFactory;
    protected $bentoBannerRepository;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CategoryBentoBannerRepositoryInterface $bentoBannerRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->bentoBannerRepository = $bentoBannerRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('entity_id');
        $model = null;

        if ($id) {
            try {
                $model = $this->bentoBannerRepository->getById($id);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('This bento banner no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Formula_CategoryBentoBanners::category_bento_banners');
        $resultPage->addBreadcrumb(__('Category Bento Banners'), __('Category Bento Banners'));
        $resultPage->addBreadcrumb(
            $id ? __('Edit Bento Banner') : __('New Bento Banner'),
            $id ? __('Edit Bento Banner') : __('New Bento Banner')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Category Bento Banners'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model && $model->getId() ? __('Edit Bento Banner') : __('New Bento Banner')
        );

        return $resultPage;
    }
}