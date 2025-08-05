<?php
namespace Formula\Ingredient\Controller\Adminhtml\Ingredient;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Formula\Ingredient\Model\ResourceModel\Ingredient\CollectionFactory;
use Formula\Ingredient\Model\IngredientFactory;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{
    protected $filter;
    protected $collectionFactory;
    protected $ingredientFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        IngredientFactory $ingredientFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->ingredientFactory = $ingredientFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        
        try {
            // Get selected IDs from mass action
            $ids = $this->getRequest()->getParam('selected');
            if (!$ids) {
                $ids = $this->getRequest()->getParam('excluded') ? [] : null;
                if ($ids === null) {
                    $this->messageManager->addErrorMessage(__('Please select ingredient(s) to delete.'));
                    return $resultRedirect->setPath('*/*/');
                }
                
                // If excluded is set, get all IDs except excluded ones
                $collection = $this->collectionFactory->create();
                $excluded = $this->getRequest()->getParam('excluded');
                if ($excluded && $excluded[0] !== '') {
                    $collection->addFieldToFilter('ingredient_id', ['nin' => $excluded]);
                }
                $ids = $collection->getAllIds();
            }
            
            if (empty($ids)) {
                $this->messageManager->addErrorMessage(__('Please select ingredient(s) to delete.'));
                return $resultRedirect->setPath('*/*/');
            }
            
            $deletedCount = 0;
            foreach ($ids as $id) {
                try {
                    $ingredient = $this->ingredientFactory->create();
                    $ingredient->load($id);
                    if ($ingredient->getId()) {
                        $ingredient->delete();
                        $deletedCount++;
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(
                        __('Error deleting ingredient ID %1: %2', $id, $e->getMessage())
                    );
                }
            }
            
            if ($deletedCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 ingredient(s) have been deleted.', $deletedCount)
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
        }
        
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Ingredient::ingredient');
    }
}