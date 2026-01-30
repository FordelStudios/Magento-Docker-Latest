<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Controller\Adminhtml\SpecialOffer;

use Formula\SpecialOffer\Api\SpecialOfferRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class InlineEdit extends Action
{
    public const ADMIN_RESOURCE = 'Formula_SpecialOffer::special_offer_save';

    public function __construct(
        Context $context,
        private readonly SpecialOfferRepositoryInterface $specialOfferRepository,
        private readonly JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $items = $this->getRequest()->getParam('items', []);
        if (empty($items)) {
            $error = true;
            $messages[] = __('Please correct the data sent.');
            return $resultJson->setData([
                'messages' => $messages,
                'error' => $error
            ]);
        }

        foreach ($items as $entityId => $itemData) {
            try {
                $specialOffer = $this->specialOfferRepository->getById((int)$entityId);

                if (isset($itemData['title'])) {
                    $specialOffer->setTitle($itemData['title']);
                }
                if (isset($itemData['subtitle'])) {
                    $specialOffer->setSubtitle($itemData['subtitle']);
                }
                if (isset($itemData['url'])) {
                    $specialOffer->setUrl($itemData['url']);
                }
                if (isset($itemData['is_active'])) {
                    $specialOffer->setIsActive((bool)$itemData['is_active']);
                }
                if (isset($itemData['sort_order'])) {
                    $specialOffer->setSortOrder((int)$itemData['sort_order']);
                }

                $this->specialOfferRepository->save($specialOffer);
            } catch (\Exception $e) {
                $error = true;
                $messages[] = "[Special Offer ID: {$entityId}] " . $e->getMessage();
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}
