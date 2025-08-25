<?php
namespace Formula\HairConcern\Controller\Adminhtml\HairConcern;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Formula\HairConcern\Model\ResourceModel\HairConcern\CollectionFactory;
use Formula\HairConcern\Model\HairConcernFactory;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{
    protected $filter;
    protected $collectionFactory;
    protected $hairconcernFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        HairConcernFactory $hairconcernFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->hairconcernFactory = $hairconcernFactory;
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
                    $this->messageManager->addErrorMessage(__('Please select hairconcern(s) to delete.'));
                    return $resultRedirect->setPath('*/*/');
                }
                
                // If excluded is set, get all IDs except excluded ones
                $collection = $this->collectionFactory->create();
                $excluded = $this->getRequest()->getParam('excluded');
                if ($excluded && $excluded[0] !== '') {
                    $collection->addFieldToFilter('hairconcern_id', ['nin' => $excluded]);
                }
                $ids = $collection->getAllIds();
            }
            
            if (empty($ids)) {
                $this->messageManager->addErrorMessage(__('Please select hairconcern(s) to delete.'));
                return $resultRedirect->setPath('*/*/');
            }
            
            $deletedCount = 0;
            foreach ($ids as $id) {
                try {
                    $hairconcern = $this->hairconcernFactory->create();
                    $hairconcern->load($id);
                    if ($hairconcern->getId()) {
                        $hairconcern->delete();
                        $deletedCount++;
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(
                        __('Error deleting hairconcern ID %1: %2', $id, $e->getMessage())
                    );
                }
            }
            
            if ($deletedCount > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 hairconcern(s) have been deleted.', $deletedCount)
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
        }
        
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_HairConcern::hairconcern');
    }
}