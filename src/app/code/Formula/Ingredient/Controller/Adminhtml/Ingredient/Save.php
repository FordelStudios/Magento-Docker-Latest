<?php
namespace Formula\Ingredient\Controller\Adminhtml\Ingredient;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\Ingredient\Model\IngredientFactory;
use Formula\Ingredient\Model\ImageUploader;
use Magento\Framework\Serialize\Serializer\Json;

class Save extends Action
{
    protected $ingredientFactory;
    protected $imageUploader;
    protected $jsonSerializer;
    protected $logger;

    public function __construct(
        Context $context,
        IngredientFactory $ingredientFactory,
        ImageUploader $imageUploader,
        Json $jsonSerializer,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->ingredientFactory = $ingredientFactory;
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
                $model = $this->ingredientFactory->create();
                
                $id = $this->getRequest()->getParam('ingredient_id');
                if ($id) {
                    $model->load($id);
                    if (!$model->getId()) {
                        $this->messageManager->addErrorMessage(__('This ingredient no longer exists.'));
                        return $resultRedirect->setPath('*/*/');
                    }
                }

                // Handle logo upload
                if (isset($data['logo']) && is_array($data['logo'])) {
                    if (!empty($data['logo'][0]['name']) && !empty($data['logo'][0]['tmp_name'])) {
                        try {
                            $this->imageUploader->setBaseTmpPath("ingredient/tmp/logo");
                            $this->imageUploader->setBasePath("ingredient/logo");
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


                // Handle benefits
                if (isset($data['benefits']) && !empty($data['benefits'])) {
                    if (is_string($data['benefits'])) {
                        $benefits = array_map('trim', explode(',', $data['benefits']));
                        $benefits = array_filter($benefits);
                        $data['benefits'] = $this->jsonSerializer->serialize($benefits);
                    }
                }

                // If it's a new entry, unset any empty ingredient_id
                if (empty($data['ingredient_id'])) {
                    unset($data['ingredient_id']);
                }

                $model->setData($data);
                $model->save();
                
                $this->messageManager->addSuccessMessage(__('The ingredient has been saved.'));

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['ingredient_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
                
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the ingredient: %1', $e->getMessage()));
                return $resultRedirect->setPath('*/*/edit', ['ingredient_id' => $this->getRequest()->getParam('ingredient_id')]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}