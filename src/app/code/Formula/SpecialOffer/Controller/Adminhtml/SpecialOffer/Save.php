<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Controller\Adminhtml\SpecialOffer;

use Formula\SpecialOffer\Api\SpecialOfferRepositoryInterface;
use Formula\SpecialOffer\Model\ImageUploader;
use Formula\SpecialOffer\Model\SpecialOfferFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'Formula_SpecialOffer::special_offer_save';

    public function __construct(
        Context $context,
        private readonly SpecialOfferRepositoryInterface $specialOfferRepository,
        private readonly SpecialOfferFactory $specialOfferFactory,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ImageUploader $imageUploader
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $entityId = isset($data['entity_id']) ? (int)$data['entity_id'] : null;

        try {
            if ($entityId) {
                $specialOffer = $this->specialOfferRepository->getById($entityId);
            } else {
                $specialOffer = $this->specialOfferFactory->create();
            }

            // Handle image upload
            $imageName = $this->processImageData($data);
            if ($imageName) {
                $data['image'] = $imageName;
            }

            // Set data
            $specialOffer->setTitle($data['title']);
            $specialOffer->setSubtitle($data['subtitle'] ?? null);
            $specialOffer->setImage($data['image']);
            $specialOffer->setUrl($data['url']);
            $specialOffer->setIsActive((bool)($data['is_active'] ?? true));
            $specialOffer->setStartDate($data['start_date'] ?? null);
            $specialOffer->setEndDate($data['end_date'] ?? null);
            $specialOffer->setSortOrder((int)($data['sort_order'] ?? 0));

            $this->specialOfferRepository->save($specialOffer);
            $this->messageManager->addSuccessMessage(__('You saved the special offer.'));
            $this->dataPersistor->clear('formula_special_offer');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $specialOffer->getEntityId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the special offer.'));
        }

        $this->dataPersistor->set('formula_special_offer', $data);

        if ($entityId) {
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $entityId]);
        }

        return $resultRedirect->setPath('*/*/new');
    }

    /**
     * Process image data from form
     */
    private function processImageData(array &$data): ?string
    {
        if (isset($data['image']) && is_array($data['image'])) {
            if (!empty($data['image'][0]['name']) && !empty($data['image'][0]['tmp_name'])) {
                // New image uploaded - move from tmp
                $imageName = $data['image'][0]['name'];
                $this->imageUploader->moveFileFromTmp($imageName);
                return $imageName;
            } elseif (!empty($data['image'][0]['name'])) {
                // Existing image - keep the name
                return $data['image'][0]['name'];
            }
        }

        // Check if image was removed
        if (isset($data['image']) && empty($data['image'])) {
            return '';
        }

        return null;
    }
}
