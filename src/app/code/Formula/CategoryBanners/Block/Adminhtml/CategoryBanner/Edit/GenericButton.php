<?php
// app/code/Formula/CategoryBanners/Block/Adminhtml/CategoryBanner/Edit/GenericButton.php
namespace Formula\CategoryBanners\Block\Adminhtml\CategoryBanner\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Formula\CategoryBanners\Model\CategoryBannerRepository;

class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var CategoryBannerRepository
     */
    protected $bannerRepository;

    /**
     * @param Context $context
     * @param CategoryBannerRepository $bannerRepository
     */
    public function __construct(
        Context $context,
        CategoryBannerRepository $bannerRepository
    ) {
        $this->context = $context;
        $this->bannerRepository = $bannerRepository;
    }

    /**
     * Return banner ID
     *
     * @return int|null
     */
    public function getBannerId()
    {
        try {
            return $this->bannerRepository->getById(
                $this->context->getRequest()->getParam('id')
            )->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}