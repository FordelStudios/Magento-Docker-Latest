<?php
namespace Formula\BodyConcern\Controller\Adminhtml\BodyConcern;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\BodyConcern\Model\BodyConcernFactory;

class Delete extends Action
{
    protected $bodyconcernFactory;

    public function __construct(
        Context $context,
        BodyConcernFactory $bodyconcernFactory
    ) {
        $this->bodyconcernFactory = $bodyconcernFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('bodyconcern_id');
        
        if ($id) {
            try {
                $model = $this->bodyconcernFactory->create();
                $model->load($id);
                $model->delete();
                
                $this->messageManager->addSuccessMessage(__('The bodyconcern has been deleted.'));
                return $resultRedirect->setPath('*/*/') ;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['bodyconcern_id' => $id]);
            }
        }
        
        $this->messageManager->addErrorMessage(__('We can\'t find a bodyconcern to delete.'));
        return $resultRedirect->setPath('*/*/') ;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_BodyConcern::bodyconcern');
    }
}