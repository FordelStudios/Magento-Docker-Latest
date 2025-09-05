<?php
namespace Formula\CategoryBentoBanners\Controller\Adminhtml\CategoryBentoBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\CategoryBentoBanners\Api\CategoryBentoBannerRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class InlineEdit extends Action
{
    const ADMIN_RESOURCE = 'Formula_CategoryBentoBanners::save';

    protected $jsonFactory;
    protected $bentoBannerRepository;

    public function __construct(
        Context $context,
        CategoryBentoBannerRepositoryInterface $bentoBannerRepository,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->bentoBannerRepository = $bentoBannerRepository;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $modelid) {
                    $model = $this->bentoBannerRepository->getById($modelid);
                    try {
                        $model->setData(array_merge($model->getData(), $postItems[$modelid]));
                        $this->bentoBannerRepository->save($model);
                    } catch (\Exception $e) {
                        $messages[] = $this->getErrorWithModelId(
                            $model,
                            __($e->getMessage())
                        );
                        $error = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    protected function getErrorWithModelId($model, $errorText)
    {
        return '[Bento Banner ID: ' . $model->getId() . '] ' . $errorText;
    }
}