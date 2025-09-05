<?php
namespace Formula\CategoryBentoBanners\Controller\Adminhtml\CategoryBentoBanner;

use Formula\CategoryBentoBanners\Model\ResourceModel\CategoryBentoBanner\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Formula\CategoryBentoBanners\Api\CategoryBentoBannerRepositoryInterface;

class MassDelete extends Action
{
    const ADMIN_RESOURCE = 'Formula_CategoryBentoBanners::delete';

    protected $filter;
    protected $collectionFactory;
    protected $bentoBannerRepository;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        CategoryBentoBannerRepositoryInterface $bentoBannerRepository
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->bentoBannerRepository = $bentoBannerRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $bentoBanner) {
            $this->bentoBannerRepository->delete($bentoBanner);
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $collectionSize));

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}