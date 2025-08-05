<?php
namespace Formula\SkinConcern\Controller\Adminhtml\SkinConcern;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Formula\SkinConcern\Model\ResourceModel\SkinConcern\CollectionFactory;
use Formula\SkinConcern\Model\SkinConcernFactory;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{
    protected $filter;
    protected $collectionFactory;
    protected $skinconcernFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        SkinConcernFactory $skinconcernFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->skinconcernFactory = $skinconcernFactory;
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
                    $this->messageManager->addErrorMessage(__('Please select skinconcern(s) to delete.'));
                    return $resultRedirect->setPath('*/*/');
                }
                
                // If excluded is set, get all IDs except excluded ones
                $collection = $this->collectionFactory->create();
                $excluded = $this->getRequest()->getParam('excluded');
                if ($excluded && $excluded[0] !== '') {
                    $collection->addFieldToFilter('skinconcern_id', ['nin' => $excluded]);
                }
                $ids = $collection->getAllIds();
            }
            
            if (empty($ids)) {
                $this->messageManager->addErrorMessage(__('Please select skinconcern(s) to delete.'));
                return $resultRedirect->setPath('*/*/');
            }
            
            $deletedCount = 0;
            foreach ($ids as $id) {
                try {
                    $skinconcern = $this->skinconcernFactory->create();
                    $skinconcern->load($id);
                    if ($skinconcern->getId()) {
                        $skinconcern->delete();
                        $deletedCount++;
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(
                        __('Error deleting skinconcern ID %1: %2', $id, $e->getMessage())
                    );
                }
            }
            
            if ($deletedCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 skinconcern(s) have been deleted.', $deletedCount)
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
        }
        
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_SkinConcern::skinconcern');
    }
}