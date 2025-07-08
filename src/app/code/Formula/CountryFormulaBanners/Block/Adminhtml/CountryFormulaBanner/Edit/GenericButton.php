<?php
// app/code/Formula/CountryFormulaBanners/Block/Adminhtml/CountryFormulaBanner/Edit/GenericButton.php
namespace Formula\CountryFormulaBanners\Block\Adminhtml\CountryFormulaBanner\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Formula\CountryFormulaBanners\Model\CountryFormulaBannerRepository;

class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var CountryFormulaBannerRepository
     */
    protected $bannerRepository;

    /**
     * @param Context $context
     * @param CountryFormulaBannerRepository $bannerRepository
     */
    public function __construct(
        Context $context,
        CountryFormulaBannerRepository $bannerRepository
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