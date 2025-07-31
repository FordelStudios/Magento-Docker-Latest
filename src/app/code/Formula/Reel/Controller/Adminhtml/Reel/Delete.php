<?php
// src/app/code/Formula/Reel/Controller/Adminhtml/Reel/Delete.php
namespace Formula\Reel\Controller\Adminhtml\Reel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\Reel\Model\ReelRepository;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
{
    protected $reelRepository;

    public function __construct(
        Context $context,
        ReelRepository $reelRepository
    ) {
        $this->reelRepository = $reelRepository;
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Reel::reel');
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('reel_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        
        if ($id) {
            try {
                $this->reelRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The reel post has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred while deleting the reel post.'));
            }
            return $resultRedirect->setPath('*/*/edit', ['reel_id' => $id]);
        }
        
        $this->messageManager->addErrorMessage(__('We can\'t find a reel post to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}