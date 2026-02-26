<?php

namespace Formula\Brand\Model;

use Formula\Brand\Api\Data\BrandInterface;
use Magento\Framework\Model\AbstractModel;

class Brand extends AbstractModel implements BrandInterface
{
    /**
     * @var string
     */
    const BRAND_ID = 'brand_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const TAGLINE = 'tagline';
    const LOGO = 'logo';
    const PROMOTIONAL_BANNERS = 'promotional_banners';
    const SALE_PAGE_BANNER = 'sale_page_banner';
    const TAGS = 'tags';
    const IS_KOREAN = 'is_korean';
    const IS_GLOBAL = 'is_global';
    const IS_JAPANESE = 'is_japanese';
    const IS_AFRICAN = 'is_african';
    const IS_INDIAN = 'is_indian';
    const IS_TRENDING = 'is_trending';
    const IS_POPULAR = 'is_popular';
    const IS_SALE = 'is_sale';
    const DISCOUNT_TEXT = 'discount_text';
    const COUNTRY = 'country';
    const CERTIFICATIONS = 'certifications';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        array $data = []
    ) {
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context, $registry);
    }

    protected function _construct()
    {
        $this->_init(\Formula\Brand\Model\ResourceModel\Brand::class);
    }

    /**
     * Get Brand Id
     * 
     * @return int|null
     */
    public function getBrandId()
    {
        return $this->getData(self::BRAND_ID);
    }

    /**
     * Set Brand Id
     * 
     * @param int $brandId
     * @return $this
     */
    public function setBrandId($brandId)
    {
        return $this->setData(self::BRAND_ID, $brandId);
    }

    /**
     * Get Brand Name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set Brand Name
     * 
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get Brand Description
     * 
     * @return string|null
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Set Brand Description
     * 
     * @param string|null $description
     * @return $this
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Get Brand Tagline
     * 
     * @return string|null
     */
    public function getTagline()
    {
        return $this->getData(self::TAGLINE);
    }

    /**
     * Set Brand Tagline
     * 
     * @param string|null $tagline
     * @return $this
     */
    public function setTagline($tagline)
    {
        return $this->setData(self::TAGLINE, $tagline);
    }

    /**
     * Get Brand Logo Path
     * 
     * @return string|null
     */
    public function getLogo()
    {
        return $this->getData(self::LOGO);
    }

    /**
     * Set Brand Logo Path
     * 
     * @param string|null $logo
     * @return $this
     */
    public function setLogo($logo)
    {
        return $this->setData(self::LOGO, $logo);
    }

    /**
     * Get Promotional Banners
     * 
     * @return string|null
     */
    public function getPromotionalBanners()
    {
        $banners = $this->getData(self::PROMOTIONAL_BANNERS);
        if ($banners && is_string($banners)) {
            try {
                return $this->jsonSerializer->unserialize($banners);
            } catch (\Exception $e) {
                return [];
            }
        }
        return $banners ?: [];
    }

    /**
     * Set Promotional Banners
     * 
     * @param string|mixed[] $banners
     * @return $this
     */
    public function setPromotionalBanners($banners)
    {
        if (is_array($banners)) {
            $banners = $this->jsonSerializer->serialize($banners);
        }
        return $this->setData(self::PROMOTIONAL_BANNERS, $banners);
    }

    /**
     * Get Sale Page Banner Path
     * 
     * @return string|null
     */
    public function getSalePageBanner()
    {
        return $this->getData(self::SALE_PAGE_BANNER);
    }

    /**
     * Set Sale Page Banner Path
     * 
     * @param string|null $salePageBanner
     * @return $this
     */
    public function setSalePageBanner($salePageBanner)
    {
        return $this->setData(self::SALE_PAGE_BANNER, $salePageBanner);
    }

    /**
     * Get Tags
     * 
     * @return string|null
     */
    public function getTags()
    {
        $tags = $this->getData(self::TAGS);
        if ($tags && is_string($tags)) {
            try {
                return $this->jsonSerializer->unserialize($tags);
            } catch (\Exception $e) {
                return [];
            }
        }
        return $tags ?: [];
    }

    /**
     * Set Tags
     * 
     * @param string|mixed[] $tags
     * @return $this
     */
    public function setTags($tags)
    {
        if (is_array($tags)) {
            $tags = $this->jsonSerializer->serialize($tags);
        }
        return $this->setData(self::TAGS, $tags);
    }


    public function getIsKorean()
    {
        return $this->getData(self::IS_KOREAN);
    }

    public function setIsKorean($isKorean)
    {
        return $this->setData(self::IS_KOREAN, $isKorean);
    }

    public function getIsGlobal()
    {
        return $this->getData(self::IS_GLOBAL);
    }

    public function setIsGlobal($isGlobal)
    {
        return $this->setData(self::IS_GLOBAL, $isGlobal);
    }

    public function getIsJapanese()
    {
        return $this->getData(self::IS_JAPANESE);
    }

    public function setIsJapanese($isJapanese)
    {
        return $this->setData(self::IS_JAPANESE, $isJapanese);
    }

    public function getIsAfrican()
    {
        return $this->getData(self::IS_AFRICAN);
    }

    public function setIsAfrican($isAfrican)
    {
        return $this->setData(self::IS_AFRICAN, $isAfrican);
    }

    public function getIsIndian()
    {
        return $this->getData(self::IS_INDIAN);
    }

    public function setIsIndian($isIndian)
    {
        return $this->setData(self::IS_INDIAN, $isIndian);
    }

    public function getIsTrending()
    {
        return $this->getData(self::IS_TRENDING);
    }

    public function setIsTrending($isTrending)
    {
        return $this->setData(self::IS_TRENDING, $isTrending);
    }

    public function getIsPopular()
    {
        return $this->getData(self::IS_POPULAR);
    }

    public function setIsPopular($isPopular)
    {
        return $this->setData(self::IS_POPULAR, $isPopular);
    }

    public function getIsSale()
    {
        return $this->getData(self::IS_SALE);
    }

    public function setIsSale($isSale)
    {
        return $this->setData(self::IS_SALE, $isSale);
    }

    public function getDiscountText()
    {
        return $this->getData(self::DISCOUNT_TEXT);
    }

    public function setDiscountText($discountText)
    {
        return $this->setData(self::DISCOUNT_TEXT, $discountText);
    }

    public function getCountry()
    {
        return $this->getData(self::COUNTRY);
    }

    public function setCountry($country)
    {
        return $this->setData(self::COUNTRY, $country);
    }

    /**
     * Get Brand Certifications
     *
     * @return string|null
     */
    public function getCertifications()
    {
        return $this->getData(self::CERTIFICATIONS);
    }

    /**
     * Set Brand Certifications
     *
     * @param string|null $certifications
     * @return $this
     */
    public function setCertifications($certifications)
    {
        return $this->setData(self::CERTIFICATIONS, $certifications);
    }

    /**
     * Get Brand Creation Time
     * 
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set Brand Creation Time
     * 
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get Brand Update Time
     * 
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set Brand Update Time
     * 
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}