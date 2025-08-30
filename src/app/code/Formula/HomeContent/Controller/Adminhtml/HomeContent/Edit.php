<?php
declare(strict_types=1);

namespace Formula\HomeContent\Controller\Adminhtml\HomeContent;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Formula\HomeContent\Api\HomeContentRepositoryInterface;
use Formula\HomeContent\Model\ResourceModel\HomeContent\CollectionFactory;
use Magento\Framework\Registry;

class Edit extends Action implements HttpGetActionInterface
{
    protected $resultPageFactory;
    protected $homeContentRepository;
    protected $collectionFactory;
    protected $coreRegistry;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        HomeContentRepositoryInterface $homeContentRepository,
        CollectionFactory $collectionFactory,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->homeContentRepository = $homeContentRepository;
        $this->collectionFactory = $collectionFactory;
        $this->coreRegistry = $coreRegistry;
    }

    public function execute()
    {
        $collection = $this->collectionFactory->create();
        $homeContent = $collection->getFirstItem();

        if ($homeContent && $homeContent->getEntityId()) {
            $this->coreRegistry->register('formula_homecontent', $homeContent);
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Edit HomePage Content'));
        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_HomeContent::home_content_manage');
    }
}