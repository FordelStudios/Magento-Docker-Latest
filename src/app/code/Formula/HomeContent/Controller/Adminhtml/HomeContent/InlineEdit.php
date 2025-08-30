<?php
declare(strict_types=1);

namespace Formula\HomeContent\Controller\Adminhtml\HomeContent;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Formula\HomeContent\Api\HomeContentRepositoryInterface;
use Formula\HomeContent\Model\ResourceModel\HomeContent\CollectionFactory;
use Psr\Log\LoggerInterface;

class InlineEdit extends Action implements HttpPostActionInterface
{
    protected $jsonFactory;
    protected $homeContentRepository;
    protected $collectionFactory;
    protected $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        HomeContentRepositoryInterface $homeContentRepository,
        LoggerInterface $logger,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->homeContentRepository = $homeContentRepository;
        $this->logger = $logger;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $entityId) {
                    try {
                        $homeContent = $this->homeContentRepository->getById($entityId);
                        $homeContentData = $postItems[$entityId];
                        
                        if (isset($homeContentData['active'])) {
                            $activeValue = (bool)$homeContentData['active'];
                            
                            // If this entity is being set to active, deactivate all others
                            if ($activeValue) {
                                $collection = $this->collectionFactory->create();
                                $collection->addFieldToFilter('entity_id', ['neq' => $entityId]);
                                
                                foreach ($collection as $item) {
                                    if ($item->getActive()) {
                                        $item->setActive(false);
                                        $this->homeContentRepository->save($item);
                                    }
                                }
                            }
                            
                            // // If trying to deactivate, check if all other entities are already inactive
                            // if (!$activeValue && $homeContent->getActive()) {
                            //     $collection = $this->collectionFactory->create();
                            //     $collection->addFieldToFilter('entity_id', ['neq' => $entityId]);
                            //     $collection->addFieldToFilter('active', 1);
                                
                            //     if ($collection->getSize() == 0) {
                            //         $messages[] = __('At least one home content must remain active.');
                            //         $error = true;
                            //         continue;
                            //     }
                            // }
                            
                            $homeContent->setActive($activeValue);
                        }
                        
                        $this->homeContentRepository->save($homeContent);
                    } catch (\Exception $e) {
                        $messages[] = $this->getErrorWithEntityId(
                            $homeContent,
                            __($e->getMessage())
                        );
                        $error = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    protected function getErrorWithEntityId($homeContent, $errorText)
    {
        return '[Home Content ID: ' . $homeContent->getEntityId() . '] ' . $errorText;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_HomeContent::home_content_manage');
    }
}