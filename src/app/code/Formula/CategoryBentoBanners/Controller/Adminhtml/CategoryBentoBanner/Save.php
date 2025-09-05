<?php
namespace Formula\CategoryBentoBanners\Controller\Adminhtml\CategoryBentoBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\CategoryBentoBanners\Api\CategoryBentoBannerRepositoryInterface;
use Formula\CategoryBentoBanners\Model\CategoryBentoBannerFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    const ADMIN_RESOURCE = 'Formula_CategoryBentoBanners::save';

    protected $bentoBannerRepository;
    protected $bentoBannerFactory;
    protected $dataPersistor;
    protected $logger;

    public function __construct(
        Context $context,
        CategoryBentoBannerRepositoryInterface $bentoBannerRepository,
        CategoryBentoBannerFactory $bentoBannerFactory,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger
    ) {
        $this->bentoBannerRepository = $bentoBannerRepository;
        $this->bentoBannerFactory = $bentoBannerFactory;
        $this->dataPersistor = $dataPersistor;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->logger->info('CategoryBentoBanners Save: Starting execute method');
        $resultRedirect = $this->resultRedirectFactory->create();
        
        $data = $this->getRequest()->getPostValue();
        $this->logger->info('CategoryBentoBanners Save: Post data received', ['data' => $data]);
        
        if ($data) {
            $id = $this->getRequest()->getParam('entity_id');
            $this->logger->info('CategoryBentoBanners Save: Entity ID', ['id' => $id]);

            try {
                if ($id) {
                    $model = $this->bentoBannerRepository->getById($id);
                    $this->logger->info('CategoryBentoBanners Save: Loaded existing model', ['model_id' => $model->getId()]);
                } else {
                    $model = $this->bentoBannerFactory->create();
                    $this->logger->info('CategoryBentoBanners Save: Created new model');
                }

                // Handle banner image data properly
                if (isset($data['banner_image'][0]['name'])) {
                    $data['banner_image'] = $data['banner_image'][0]['name'];
                    $this->logger->info('CategoryBentoBanners Save: Image from name', ['image' => $data['banner_image']]);
                } elseif (isset($data['banner_image'][0]['url'])) {
                    // Extract filename from URL if it contains the full path
                    $urlParts = explode('/', $data['banner_image'][0]['url']);
                    $data['banner_image'] = end($urlParts);
                    $this->logger->info('CategoryBentoBanners Save: Image from URL', ['image' => $data['banner_image']]);
                }

                // Clean up empty entity_id for new records
                if (isset($data['entity_id']) && empty($data['entity_id'])) {
                    unset($data['entity_id']);
                    $this->logger->info('CategoryBentoBanners Save: Removed empty entity_id for new record');
                }
                
                // Remove form_key from data
                if (isset($data['form_key'])) {
                    unset($data['form_key']);
                    $this->logger->info('CategoryBentoBanners Save: Removed form_key from data');
                }

                $this->logger->info('CategoryBentoBanners Save: Data before setting to model', ['data' => $data]);

                // Set the data to the model
                $model->addData($data);
                
                $this->logger->info('CategoryBentoBanners Save: Model data after setting', ['model_data' => $model->getData()]);
                
                $savedModel = $this->bentoBannerRepository->save($model);
                $this->logger->info('CategoryBentoBanners Save: Model saved successfully', ['saved_model_id' => $savedModel->getId()]);

                $this->messageManager->addSuccessMessage(__('You saved the bento banner.'));
                $this->dataPersistor->clear('categorybentobanner_form_data');
                
                if ($this->getRequest()->getParam('back')) {
                    $this->logger->info('CategoryBentoBanners Save: Redirecting to edit page');
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $savedModel->getId()]);
                }
                $this->logger->info('CategoryBentoBanners Save: Redirecting to listing page');
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->logger->error('CategoryBentoBanners Save: Exception occurred', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the bento banner: ' . $e->getMessage()));
                $this->dataPersistor->set('categorybentobanner_form_data', $data);
            }

            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('entity_id')]);
        }

        $this->logger->info('CategoryBentoBanners Save: No data received, redirecting to listing');
        return $resultRedirect->setPath('*/*/');
    }
}