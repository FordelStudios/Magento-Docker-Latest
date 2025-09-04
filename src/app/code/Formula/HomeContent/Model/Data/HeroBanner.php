<?php
declare(strict_types=1);

namespace Formula\HomeContent\Model\Data;

use Formula\HomeContent\Api\Data\HeroBannerInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class HeroBanner extends AbstractExtensibleObject implements HeroBannerInterface
{
    const IMAGE = 'image';
    const URL = 'url';

    public function getImage()
    {
        return $this->_get(self::IMAGE) ?: '';
    }

    public function setImage($image)
    {
        return $this->setData(self::IMAGE, $image);
    }

    public function getUrl()
    {
        return $this->_get(self::URL) ?: '';
    }

    public function setUrl($url)
    {
        return $this->setData(self::URL, $url);
    }
}