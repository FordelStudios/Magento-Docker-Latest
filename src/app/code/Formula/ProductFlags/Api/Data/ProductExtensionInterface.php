<?php
namespace Formula\ProductFlags\Api\Data;

interface ProductExtensionInterface extends \Magento\Catalog\Api\Data\ProductExtensionInterface
{
    /**
     * @return bool|null
     */
    public function getGiftset();

    /**
     * @param bool $giftset
     * @return $this
     */
    public function setGiftset($giftset);

    /**
     * @return bool|null
     */
    public function getNewArrival();

    /**
     * @param bool $newArrival
     * @return $this
     */
    public function setNewArrival($newArrival);

    /**
     * @return bool|null
     */
    public function getTrending();

    /**
     * @param bool $trending
     * @return $this
     */
    public function setTrending($trending);

    /**
     * @return bool|null
     */
    public function getPopular();

    /**
     * @param bool $popular
     * @return $this
     */
    public function setPopular($popular);
}
