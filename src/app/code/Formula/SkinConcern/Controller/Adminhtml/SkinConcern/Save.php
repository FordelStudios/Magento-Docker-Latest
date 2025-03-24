<?php
namespace Formula\SkinConcern\Controller\Adminhtml\SkinConcern;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\SkinConcern\Model\SkinConcernFactory;
use Formula\SkinConcern\Model\ImageUploader;
use Magento\Framework\Serialize\Serializer\Json;

class Save extends Action
{
    protected $skinconcernFactory;
    protected $imageUploader;
    protected $jsonSerializer;
    protected $logger;

    public function __construct(
        Context $context,
        SkinConcernFactory $skinconcernFactory,
        ImageUploader $imageUploader,
        Json $jsonSerializer,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->skinconcernFactory = $skinconcernFactory;
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
                $model = $this->skinconcernFactory->create();
                
                $id = $this->getRequest()->getParam('skinconcern_id');
                if ($id) {
                    $model->load($id);
                    if (!$model->getId()) {
                        $this->messageManager->addErrorMessage(__('This skinconcern no longer exists.'));
                        return $resultRedirect->setPath('*/*/');
                    }
                }

                // Handle logo upload
                if (isset($data['logo']) && is_array($data['logo'])) {
                    if (!empty($data['logo'][0]['name']) && !empty($data['logo'][0]['tmp_name'])) {
                        try {
                            $this->imageUploader->setBaseTmpPath("skinconcern/tmp/logo");
                            $this->imageUploader->setBasePath("skinconcern/logo");
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

                // Handle promotional banners upload
                if (isset($data['promotional_banners']) && is_array($data['promotional_banners'])) {
                    $banners = [];
                    foreach ($data['promotional_banners'] as $banner) {
                        if (!empty($banner['name']) && !empty($banner['tmp_name'])) {
                            try {
                                $this->imageUploader->setBaseTmpPath("skinconcern/tmp/banner");
                                $this->imageUploader->setBasePath("skinconcern/banner");
                                $banners[] = $banner['name'];
                                $this->imageUploader->moveFileFromTmp($banner['name']);
                            } catch (\Exception $e) {
                                $this->logger->critical($e);
                                $this->messageManager->addExceptionMessage($e, __('Error processing banner upload: %1', $e->getMessage()));
                            }
                        } elseif (!empty($banner['name']) && empty($banner['tmp_name'])) {
                            $banners[] = $banner['name'];
                        }
                    }
                    if (!empty($banners)) {
                        $data['promotional_banners'] = $this->jsonSerializer->serialize($banners);
                    } else {
                        unset($data['promotional_banners']);
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

                // If it's a new entry, unset any empty skinconcern_id
                if (empty($data['skinconcern_id'])) {
                    unset($data['skinconcern_id']);
                }

                $model->setData($data);
                $model->save();
                
                $this->messageManager->addSuccessMessage(__('The skinconcern has been saved.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['skinconcern_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
                
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the skinconcern: %1', $e->getMessage()));
                return $resultRedirect->setPath('*/*/edit', ['skinconcern_id' => $this->getRequest()->getParam('skinconcern_id')]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}