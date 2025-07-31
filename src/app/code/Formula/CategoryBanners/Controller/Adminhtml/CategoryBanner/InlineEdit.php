<?php
// app/code/Formula/CategoryBanners/Controller/Adminhtml/CategoryBanner/InlineEdit.php
namespace Formula\CategoryBanners\Controller\Adminhtml\CategoryBanner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Formula\CategoryBanners\Model\CategoryBannerRepository;
use Magento\Framework\Exception\LocalizedException;

class InlineEdit extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Formula_CategoryBanners::save';

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var CategoryBannerRepository
     */
    protected $bannerRepository;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CategoryBannerRepository $bannerRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CategoryBannerRepository $bannerRepository
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->bannerRepository = $bannerRepository;
    }

    /**
     * Inline edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        foreach ($postItems as $bannerId => $bannerData) {
            try {
                $banner = $this->bannerRepository->getById($bannerId);
                $banner->setData(array_merge($banner->getData(), $bannerData));
                $this->bannerRepository->save($banner);
            } catch (LocalizedException $e) {
                $messages[] = $this->getErrorWithBannerId($banner, $e->getMessage());
                $error = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithBannerId(
                    $banner,
                    __('Something went wrong while saving the banner.')
                );
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    /**
     * Add banner ID to error message
     *
     * @param \Formula\CategoryBanners\Model\CategoryBanner $banner
     * @param string $errorText
     * @return string
     */
    protected function getErrorWithBannerId($banner, $errorText)
    {
        return '[Banner ID: ' . $banner->getId() . '] ' . $errorText;
    }
}