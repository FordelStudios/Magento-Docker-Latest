<?php
// app/code/Formula/CountryFormulaBanners/Controller/Adminhtml/CountryFormulaBanner/Save.php
namespace Formula\CountryFormulaBanners\Controller\Adminhtml\CountryFormulaBanner;

use Formula\CountryFormulaBanners\Model\CountryFormulaBanner;
use Formula\CountryFormulaBanners\Model\CountryFormulaBannerFactory;
use Formula\CountryFormulaBanners\Model\CountryFormulaBannerRepository;
use Formula\CountryFormulaBanners\Model\Uploader;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Formula_CountryFormulaBanners::save';

    /**
     * @var CountryFormulaBannerFactory
     */
    private $bannerFactory;

    /**
     * @var CountryFormulaBannerRepository
     */
    private $bannerRepository;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @param Context $context
     * @param CountryFormulaBannerFactory $bannerFactory
     * @param CountryFormulaBannerRepository $bannerRepository
     * @param DataPersistorInterface $dataPersistor
     * @param Uploader $uploader
     */
    public function __construct(
        Context $context,
        CountryFormulaBannerFactory $bannerFactory,
        CountryFormulaBannerRepository $bannerRepository,
        DataPersistorInterface $dataPersistor,
        Uploader $uploader
    ) {
        parent::__construct($context);
        $this->bannerFactory = $bannerFactory;
        $this->bannerRepository = $bannerRepository;
        $this->dataPersistor = $dataPersistor;
        $this->uploader = $uploader;
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        
        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        // Handle various formats of is_active
        if (isset($data['is_active'])) {
            if (is_string($data['is_active'])) {
                if (strtolower($data['is_active']) === 'true' || $data['is_active'] === '1') {
                    $data['is_active'] = 1;
                } else if (strtolower($data['is_active']) === 'false' || $data['is_active'] === '0') {
                    $data['is_active'] = 0;
                }
            } else if (is_bool($data['is_active'])) {
                $data['is_active'] = $data['is_active'] ? 1 : 0;
            }
        }

        
        if (empty($data['entity_id'])) {
            $data['entity_id'] = null;
        }

        /** @var CountryFormulaBanner $model */
        $model = $this->bannerFactory->create();

        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $model = $this->bannerRepository->getById($id);
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage(__('This banner no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
        }

        // Process banner image
        if (isset($data['banner_image']) && is_array($data['banner_image'])) {
            if (!empty($data['banner_image'][0]['tmp_name'])) {
                $data['banner_image'] = $data['banner_image'][0]['name'];
                $this->uploader->moveFileFromTmp($data['banner_image']);
            } elseif (isset($data['banner_image'][0]['name']) && isset($data['banner_image'][0]['url'])) {
                $data['banner_image'] = $data['banner_image'][0]['name'];
            } else {
                unset($data['banner_image']);
            }
        } else {
            // Not an array, we'll keep it as is
        }

        // Set current timestamp for created_at and updated_at if new record
        if (!$id) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        } else {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        try {
            $model->setData($data);
            $this->bannerRepository->save($model);
            $this->messageManager->addSuccessMessage(__('You saved the banner.'));
            $this->dataPersistor->clear('country_formula_banner');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the banner.'));
        }

        $this->dataPersistor->set('country_formula_banner', $data);
        return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
    }
}