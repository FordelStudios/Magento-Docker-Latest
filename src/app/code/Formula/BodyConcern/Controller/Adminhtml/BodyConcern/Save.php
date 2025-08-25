<?php
namespace Formula\BodyConcern\Controller\Adminhtml\BodyConcern;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\BodyConcern\Model\BodyConcernFactory;
use Formula\BodyConcern\Model\ImageUploader;
use Magento\Framework\Serialize\Serializer\Json;

class Save extends Action
{
    protected $bodyconcernFactory;
    protected $imageUploader;
    protected $jsonSerializer;
    protected $logger;

    public function __construct(
        Context $context,
        BodyConcernFactory $bodyconcernFactory,
        ImageUploader $imageUploader,
        Json $jsonSerializer,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->bodyconcernFactory = $bodyconcernFactory;
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
                $model = $this->bodyconcernFactory->create();
                
                $id = $this->getRequest()->getParam('bodyconcern_id');
                if ($id) {
                    $model->load($id);
                    if (!$model->getId()) {
                        $this->messageManager->addErrorMessage(__('This bodyconcern no longer exists.'));
                        return $resultRedirect->setPath('*/*/');
                    }
                }

                // Handle logo upload
                if (isset($data['logo']) && is_array($data['logo'])) {
                    if (!empty($data['logo'][0]['name']) && !empty($data['logo'][0]['tmp_name'])) {
                        try {
                            $this->imageUploader->setBaseTmpPath("bodyconcern/tmp/logo");
                            $this->imageUploader->setBasePath("bodyconcern/logo");
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

                // If it's a new entry, unset any empty bodyconcern_id
                if (empty($data['bodyconcern_id'])) {
                    unset($data['bodyconcern_id']);
                }

                $model->setData($data);
                $model->save();
                
                $this->messageManager->addSuccessMessage(__('The bodyconcern has been saved.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['bodyconcern_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
                
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the bodyconcern: %1', $e->getMessage()));
                return $resultRedirect->setPath('*/*/edit', ['bodyconcern_id' => $this->getRequest()->getParam('bodyconcern_id')]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}