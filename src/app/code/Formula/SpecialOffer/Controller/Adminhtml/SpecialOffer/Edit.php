<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Controller\Adminhtml\SpecialOffer;

use Formula\SpecialOffer\Api\SpecialOfferRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Formula_SpecialOffer::special_offer_save';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly SpecialOfferRepositoryInterface $specialOfferRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $entityId = (int)$this->getRequest()->getParam('entity_id');
        $title = __('New Special Offer');

        if ($entityId) {
            try {
                $specialOffer = $this->specialOfferRepository->getById($entityId);
                $title = __('Edit Special Offer: %1', $specialOffer->getTitle());
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This special offer no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Formula_SpecialOffer::special_offer');
        $resultPage->getConfig()->getTitle()->prepend($title);

        return $resultPage;
    }
}
