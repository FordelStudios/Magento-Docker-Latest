<?php
// app/code/Formula/CountryFormulaBanners/Controller/Adminhtml/CountryFormulaBanner/Index.php
namespace Formula\CountryFormulaBanners\Controller\Adminhtml\CountryFormulaBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Formula_CountryFormulaBanners::manage';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Formula_CountryFormulaBanners::manage');
        $resultPage->addBreadcrumb(__('Formula'), __('Formula'));
        $resultPage->addBreadcrumb(__('Country Formula Banners'), __('Country Formula Banners'));
        $resultPage->getConfig()->getTitle()->prepend(__('Country Formula Banners'));

        return $resultPage;
    }
}