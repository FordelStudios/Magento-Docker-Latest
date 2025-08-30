<?php
declare(strict_types=1);

namespace Formula\HomeContent\Controller\Adminhtml\HomeContent;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Formula\HomeContent\Model\ResourceModel\HomeContent\CollectionFactory;
use Formula\HomeContent\Api\HomeContentRepositoryInterface;
use Psr\Log\LoggerInterface;

class MassDelete extends Action implements HttpPostActionInterface
{
    protected $filter;
    protected $collectionFactory;
    protected $homeContentRepository;
    protected $logger;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        HomeContentRepositoryInterface $homeContentRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->homeContentRepository = $homeContentRepository;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $collectionSize = $collection->getSize();
            $deletedItems = 0;

            foreach ($collection as $homeContent) {
                try {
                    $this->homeContentRepository->delete($homeContent);
                    $deletedItems++;
                } catch (\Exception $e) {
                    $this->logger->error('Error deleting home content ID: ' . $homeContent->getId() . ' - ' . $e->getMessage());
                }
            }

            if ($deletedItems > 0) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 record(s) have been deleted.', $deletedItems)
                );
            }

            if ($deletedItems < $collectionSize) {
                $failedItems = $collectionSize - $deletedItems;
                $this->messageManager->addErrorMessage(
                    __('%1 record(s) could not be deleted.', $failedItems)
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while deleting the records.'));
            $this->logger->error($e->getMessage());
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_HomeContent::home_content_manage');
    }
}