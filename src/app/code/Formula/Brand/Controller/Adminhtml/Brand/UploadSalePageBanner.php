<?php
namespace Formula\Brand\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\Brand\Model\ImageUploader;
use Magento\Framework\Controller\ResultFactory;

class UploadSalePageBanner extends Action
{
    protected $imageUploader;

    public function __construct(
        Context $context,
        ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
    }

    public function execute()
    {
        try {
            $this->imageUploader->setBaseTmpPath("brand/tmp/sale_page_banner");
            $this->imageUploader->setBasePath("brand/sale_page_banner");
            $result = $this->imageUploader->saveFileToTmpDir('sale_page_banner');
            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}