<?php
namespace Formula\CategoryBentoBanners\Controller\Adminhtml\CategoryBentoBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\CategoryBentoBanners\Api\CategoryBentoBannerRepositoryInterface;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Formula_CategoryBentoBanners::delete';

    protected $bentoBannerRepository;

    public function __construct(
        Context $context,
        CategoryBentoBannerRepositoryInterface $bentoBannerRepository
    ) {
        $this->bentoBannerRepository = $bentoBannerRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        
        $id = $this->getRequest()->getParam('entity_id');
        if ($id) {
            try {
                $this->bentoBannerRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('You deleted the bento banner.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $resultRedirect->setPath('*/*/');
    }
}