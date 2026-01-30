<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Block\Adminhtml\SpecialOffer\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\UrlInterface;

class GenericButton
{
    protected UrlInterface $urlBuilder;

    public function __construct(
        protected readonly Context $context
    ) {
        $this->urlBuilder = $context->getUrlBuilder();
    }

    /**
     * Get entity ID from request
     */
    public function getEntityId(): ?int
    {
        $id = $this->context->getRequest()->getParam('entity_id');
        return $id ? (int)$id : null;
    }

    /**
     * Generate URL by route and parameters
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
