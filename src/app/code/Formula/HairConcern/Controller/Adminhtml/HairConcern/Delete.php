<?php
namespace Formula\HairConcern\Controller\Adminhtml\HairConcern;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\HairConcern\Model\HairConcernFactory;

class Delete extends Action
{
    protected $hairconcernFactory;

    public function __construct(
        Context $context,
        HairConcernFactory $hairconcernFactory
    ) {
        $this->hairconcernFactory = $hairconcernFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('hairconcern_id');
        
        if ($id) {
            try {
                $model = $this->hairconcernFactory->create();
                $model->load($id);
                $model->delete();
                
                $this->messageManager->addSuccessMessage(__('The hairconcern has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['hairconcern_id' => $id]);
            }
        }
        
        $this->messageManager->addErrorMessage(__('We can\'t find a hairconcern to delete.'));
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_HairConcern::hairconcern');
    }
}