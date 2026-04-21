<?php
namespace Formula\WebpUpload\Plugin;

use Magento\Framework\Image\Adapter\Gd2;

class Gd2WebpSupport
{
    public function beforeOpen(Gd2 $subject, $filename)
    {
        \Closure::bind(function () {
            if (!isset(self::$_callbacks[\IMAGETYPE_WEBP])) {
                self::$_callbacks[\IMAGETYPE_WEBP] = [
                    'output' => 'imagewebp',
                    'create' => 'imagecreatefromwebp',
                ];
            }
        }, null, Gd2::class)();

        return [$filename];
    }
}
