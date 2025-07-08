<?php
// app/code/Formula/CountryFormulaBanners/Controller/Adminhtml/CountryFormulaBanner/Edit.php
namespace Formula\CountryFormulaBanners\Controller\Adminhtml\CountryFormulaBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Formula\CountryFormulaBanners\Model\CountryFormulaBannerRepository;
use Magento\Framework\Exception\NoSuchEntityException;

class Edit extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Formula_CountryFormulaBanners::manage';

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var CountryFormulaBannerRepository
     */
    protected $bannerRepository;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param CountryFormulaBannerRepository $bannerRepository
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        CountryFormulaBannerRepository $bannerRepository
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->bannerRepository = $bannerRepository;
        parent::__construct($context);
    }

    /**
     * Edit or create banner
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create(\Formula\CountryFormulaBanners\Model\CountryFormulaBanner::class);

        if ($id) {
            try {
                $model = $this->bannerRepository->getById($id);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        // Register model to use later in blocks
        $this->coreRegistry->register('country_formula_banner', $model);

        // Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Formula_CountryFormulaBanners::manage')
            ->addBreadcrumb(
                $id ? __('Edit Banner') : __('New Banner'),
                $id ? __('Edit Banner') : __('New Banner')
            );
        $resultPage->getConfig()->getTitle()->prepend(__('Country Formula Banners'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? $model->getTitle() : __('New Banner'));

        return $resultPage;
    }
}