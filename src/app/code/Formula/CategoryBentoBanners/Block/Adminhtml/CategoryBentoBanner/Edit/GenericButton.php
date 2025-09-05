<?php
namespace Formula\CategoryBentoBanners\Block\Adminhtml\CategoryBentoBanner\Edit;

use Magento\Backend\Block\Widget\Context;
use Formula\CategoryBentoBanners\Api\CategoryBentoBannerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class GenericButton
{
    protected $context;
    protected $bentoBannerRepository;

    public function __construct(
        Context $context,
        CategoryBentoBannerRepositoryInterface $bentoBannerRepository
    ) {
        $this->context = $context;
        $this->bentoBannerRepository = $bentoBannerRepository;
    }

    /**
     * Return model ID
     *
     * @return int|null
     */
    public function getModelId()
    {
        try {
            return $this->bentoBannerRepository->getById(
                $this->context->getRequest()->getParam('entity_id')
            )->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}