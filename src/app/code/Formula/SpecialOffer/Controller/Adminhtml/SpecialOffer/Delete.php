<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Controller\Adminhtml\SpecialOffer;

use Formula\SpecialOffer\Api\SpecialOfferRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Formula_SpecialOffer::special_offer_delete';

    public function __construct(
        Context $context,
        private readonly SpecialOfferRepositoryInterface $specialOfferRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $entityId = (int)$this->getRequest()->getParam('entity_id');

        if (!$entityId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a special offer to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->specialOfferRepository->deleteById($entityId);
            $this->messageManager->addSuccessMessage(__('You deleted the special offer.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while deleting the special offer.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
