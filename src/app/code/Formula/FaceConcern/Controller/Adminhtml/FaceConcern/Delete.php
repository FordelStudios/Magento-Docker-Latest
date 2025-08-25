<?php
namespace Formula\FaceConcern\Controller\Adminhtml\FaceConcern;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\FaceConcern\Model\FaceConcernFactory;

class Delete extends Action
{
    protected $faceconcernFactory;

    public function __construct(
        Context $context,
        FaceConcernFactory $faceconcernFactory
    ) {
        $this->faceconcernFactory = $faceconcernFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('faceconcern_id');
        
        if ($id) {
            try {
                $model = $this->faceconcernFactory->create();
                $model->load($id);
                $model->delete();
                
                $this->messageManager->addSuccessMessage(__('The faceconcern has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['faceconcern_id' => $id]);
            }
        }
        
        $this->messageManager->addErrorMessage(__('We can\'t find a faceconcern to delete.'));
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_FaceConcern::faceconcern');
    }
}