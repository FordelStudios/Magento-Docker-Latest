<?php
namespace Formula\Brand\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\Brand\Model\BrandFactory;
use Formula\Brand\Model\ImageUploader;
use Magento\Framework\Serialize\Serializer\Json;

class Save extends Action
{
    protected $brandFactory;
    protected $imageUploader;
    protected $jsonSerializer;
    protected $logger;

    public function __construct(
        Context $context,
        BrandFactory $brandFactory,
        ImageUploader $imageUploader,
        Json $jsonSerializer,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->brandFactory = $brandFactory;
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
                $model = $this->brandFactory->create();
                
                $id = $this->getRequest()->getParam('brand_id');
                if ($id) {
                    $model->load($id);
                    if (!$model->getId()) {
                        $this->messageManager->addErrorMessage(__('This brand no longer exists.'));
                        return $resultRedirect->setPath('*/*/');
                    }
                }

                // Handle logo upload
                if (isset($data['logo']) && is_array($data['logo'])) {
                    if (!empty($data['logo'][0]['name']) && !empty($data['logo'][0]['tmp_name'])) {
                        try {
                            $this->imageUploader->setBaseTmpPath("brand/tmp/logo");
                            $this->imageUploader->setBasePath("brand/logo");
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
                                $this->imageUploader->setBaseTmpPath("brand/tmp/banner");
                                $this->imageUploader->setBasePath("brand/banner");
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

                // If it's a new entry, unset any empty brand_id
                if (empty($data['brand_id'])) {
                    unset($data['brand_id']);
                }

                $model->setData($data);
                $model->save();
                
                $this->messageManager->addSuccessMessage(__('The brand has been saved.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['brand_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
                
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the brand: %1', $e->getMessage()));
                return $resultRedirect->setPath('*/*/edit', ['brand_id' => $this->getRequest()->getParam('brand_id')]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}