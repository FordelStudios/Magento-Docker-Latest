<?php
namespace Formula\Ingredient\Controller\Adminhtml\Ingredient;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\Ingredient\Model\IngredientFactory;

class Delete extends Action
{
    protected $ingredientFactory;

    public function __construct(
        Context $context,
        IngredientFactory $ingredientFactory
    ) {
        $this->ingredientFactory = $ingredientFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('ingredient_id');
        
        if ($id) {
            try {
                $model = $this->ingredientFactory->create();
                $model->load($id);
                $model->delete();
                
                $this->messageManager->addSuccessMessage(__('The ingredient has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['ingredient_id' => $id]);
            }
        }
        
        $this->messageManager->addErrorMessage(__('We can\'t find a ingredient to delete.'));
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Ingredient::ingredient');
    }
}