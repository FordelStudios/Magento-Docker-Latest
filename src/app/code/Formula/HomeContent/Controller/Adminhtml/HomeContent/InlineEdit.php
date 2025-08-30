<?php
declare(strict_types=1);

namespace Formula\HomeContent\Controller\Adminhtml\HomeContent;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Formula\HomeContent\Api\HomeContentRepositoryInterface;
use Psr\Log\LoggerInterface;

class InlineEdit extends Action implements HttpPostActionInterface
{
    protected $jsonFactory;
    protected $homeContentRepository;
    protected $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        HomeContentRepositoryInterface $homeContentRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->homeContentRepository = $homeContentRepository;
        $this->logger = $logger;
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
                            $homeContent->setActive((bool)$homeContentData['active']);
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