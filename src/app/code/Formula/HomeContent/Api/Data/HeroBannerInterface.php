<?php
declare(strict_types=1);

namespace Formula\HomeContent\Api\Data;

interface HeroBannerInterface
{
    /**
     * Get image
     *
     * @return string
     */
    public function getImage();

    /**
     * Set image
     *
     * @param string $image
     * @return $this
     */
    public function setImage($image);

    /**
     * Get URL
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set URL
     *
     * @param string $url
     * @return $this
     */
    public function setUrl($url);
}