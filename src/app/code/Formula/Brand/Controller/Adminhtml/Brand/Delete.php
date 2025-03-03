<?php
namespace Formula\Brand\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\Brand\Model\BrandFactory;

class Delete extends Action
{
    protected $brandFactory;

    public function __construct(
        Context $context,
        BrandFactory $brandFactory
    ) {
        $this->brandFactory = $brandFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('brand_id');
        
        if ($id) {
            try {
                $model = $this->brandFactory->create();
                $model->load($id);
                $model->delete();
                
                $this->messageManager->addSuccessMessage(__('The brand has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['brand_id' => $id]);
            }
        }
        
        $this->messageManager->addErrorMessage(__('We can\'t find a brand to delete.'));
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Brand::brand');
    }
}