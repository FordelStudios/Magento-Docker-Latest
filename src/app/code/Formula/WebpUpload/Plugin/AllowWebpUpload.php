<?php
namespace Formula\WebpUpload\Plugin;

use Magento\Catalog\Controller\Adminhtml\Product\Gallery\Upload;

class AllowWebpUpload
{
    public function aroundExecute(Upload $subject, callable $proceed)
    {
        \Closure::bind(function ($subject) {
            $subject->allowedMimeTypes['webp'] = 'image/webp';
        }, null, Upload::class)($subject);

        return $proceed();
    }
}
