<?php
namespace Formula\SkinType\Controller\Adminhtml\SkinType;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\SkinType\Model\SkinTypeFactory;

class Delete extends Action
{
    protected $skintypeFactory;

    public function __construct(
        Context $context,
        SkinTypeFactory $skintypeFactory
    ) {
        $this->skintypeFactory = $skintypeFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('skintype_id');
        
        if ($id) {
            try {
                $model = $this->skintypeFactory->create();
                $model->load($id);
                $model->delete();
                
                $this->messageManager->addSuccessMessage(__('The skintype has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['skintype_id' => $id]);
            }
        }
        
        $this->messageManager->addErrorMessage(__('We can\'t find a skintype to delete.'));
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_SkinType::skintype');
    }
}