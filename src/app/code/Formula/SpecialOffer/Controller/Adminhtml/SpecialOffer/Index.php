<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Controller\Adminhtml\SpecialOffer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Formula_SpecialOffer::special_offer';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Formula_SpecialOffer::special_offer');
        $resultPage->getConfig()->getTitle()->prepend(__('Special Offers'));

        return $resultPage;
    }
}
