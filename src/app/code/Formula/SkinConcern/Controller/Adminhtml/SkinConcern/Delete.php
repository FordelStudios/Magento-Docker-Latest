<?php
namespace Formula\SkinConcern\Controller\Adminhtml\SkinConcern;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\SkinConcern\Model\SkinConcernFactory;

class Delete extends Action
{
    protected $skinconcernFactory;

    public function __construct(
        Context $context,
        SkinConcernFactory $skinconcernFactory
    ) {
        $this->skinconcernFactory = $skinconcernFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('skinconcern_id');
        
        if ($id) {
            try {
                $model = $this->skinconcernFactory->create();
                $model->load($id);
                $model->delete();
                
                $this->messageManager->addSuccessMessage(__('The skinconcern has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['skinconcern_id' => $id]);
            }
        }
        
        $this->messageManager->addErrorMessage(__('We can\'t find a skinconcern to delete.'));
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_SkinConcern::skinconcern');
    }
}