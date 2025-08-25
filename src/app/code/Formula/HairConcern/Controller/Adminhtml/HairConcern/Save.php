<?php
namespace Formula\HairConcern\Controller\Adminhtml\HairConcern;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\HairConcern\Model\HairConcernFactory;
use Formula\HairConcern\Model\ImageUploader;
use Magento\Framework\Serialize\Serializer\Json;

class Save extends Action
{
    protected $hairconcernFactory;
    protected $imageUploader;
    protected $jsonSerializer;
    protected $logger;

    public function __construct(
        Context $context,
        HairConcernFactory $hairconcernFactory,
        ImageUploader $imageUploader,
        Json $jsonSerializer,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->hairconcernFactory = $hairconcernFactory;
        $this->imageUploader = $imageUploader;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            try {
                $model = $this->hairconcernFactory->create();
                
                $id = $this->getRequest()->getParam('hairconcern_id');
                if ($id) {
                    $model->load($id);
                    if (!$model->getId()) {
                        $this->messageManager->addErrorMessage(__('This hairconcern no longer exists.'));
                        return $resultRedirect->setPath('*/*/');
                    }
                }

                // Handle logo upload
                if (isset($data['logo']) && is_array($data['logo'])) {
                    if (!empty($data['logo'][0]['name']) && !empty($data['logo'][0]['tmp_name'])) {
                        try {
                            $this->imageUploader->setBaseTmpPath("hairconcern/tmp/logo");
                            $this->imageUploader->setBasePath("hairconcern/logo");
                            $data['logo'] = $data['logo'][0]['name'];
                            $this->imageUploader->moveFileFromTmp($data['logo']);
                        } catch (\Exception $e) {
                            $this->logger->critical($e);
                            $this->messageManager->addExceptionMessage($e, __('Error processing logo upload: %1', $e->getMessage()));
                        }
                    } elseif (!empty($data['logo'][0]['name']) && empty($data['logo'][0]['tmp_name'])) {
                        $data['logo'] = $data['logo'][0]['name'];
                    } else {
                        unset($data['logo']);
                    }
                }

                // Handle tags
                if (isset($data['tags']) && !empty($data['tags'])) {
                    if (is_string($data['tags'])) {
                        $tags = array_map('trim', explode(',', $data['tags']));
                        $tags = array_filter($tags);
                        $data['tags'] = $this->jsonSerializer->serialize($tags);
                    }
                }

                // If it's a new entry, unset any empty hairconcern_id
                if (empty($data['hairconcern_id'])) {
                    unset($data['hairconcern_id']);
                }

                $model->setData($data);
                $model->save();
                
                $this->messageManager->addSuccessMessage(__('The hairconcern has been saved.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['hairconcern_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
                
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the hairconcern: %1', $e->getMessage()));
                return $resultRedirect->setPath('*/*/edit', ['hairconcern_id' => $this->getRequest()->getParam('hairconcern_id')]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}