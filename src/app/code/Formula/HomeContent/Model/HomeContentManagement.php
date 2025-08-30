<?php
declare(strict_types=1);

namespace Formula\HomeContent\Model;

use Formula\HomeContent\Api\Data\HomeContentResponseInterface;
use Formula\HomeContent\Api\Data\HomeContentResponseInterfaceFactory;
use Formula\HomeContent\Api\Data\KoreanIngredientInterfaceFactory;
use Formula\HomeContent\Api\HomeContentManagementInterface;
use Formula\HomeContent\Api\HomeContentRepositoryInterface;
use Formula\HomeContent\Model\ResourceModel\HomeContent\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class HomeContentManagement implements HomeContentManagementInterface
{
    protected $homeContentRepository;
    protected $collectionFactory;
    protected $storeManager;
    protected $homeContentResponseFactory;
    protected $koreanIngredientFactory;

    public function __construct(
        HomeContentRepositoryInterface $homeContentRepository,
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        HomeContentResponseInterfaceFactory $homeContentResponseFactory,
        KoreanIngredientInterfaceFactory $koreanIngredientFactory
    ) {
        $this->homeContentRepository = $homeContentRepository;
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->homeContentResponseFactory = $homeContentResponseFactory;
        $this->koreanIngredientFactory = $koreanIngredientFactory;
    }

    public function getHomeContent()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('active', 1);
        $collection->setPageSize(1);
        $homeContent = $collection->getFirstItem();

        $response = $this->homeContentResponseFactory->create();

        if (!$homeContent->getEntityId()) {
            $response->setHeroBanners([]);
            $response->setFiveStepRoutineBanner('');
            $response->setThreeStepRoutineBanner('');
            $response->setDiscoverYourFormulaBanner('');
            $response->setBestOfKoreanFormulaBanner('');
            $response->setDiscoverKoreanIngredientsBanners([]);
            $response->setPerfectGiftImage('');
            $response->setBottomBanner('');
            return $response;
        }

        $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        $heroBanners = $homeContent->getHeroBanners();
        foreach ($heroBanners as $key => $banner) {
            if ($banner && !filter_var($banner, FILTER_VALIDATE_URL)) {
                $heroBanners[$key] = $baseUrl . $banner;
            }
        }

        $koreanIngredientsBanners = $homeContent->getDiscoverKoreanIngredientsBanners();
        $koreanIngredientsObjects = [];
        foreach ($koreanIngredientsBanners as $banner) {
            $ingredient = $this->koreanIngredientFactory->create();
            // Handle both 'ingredientId' and 'ingredient_id' field names
            $ingredientId = $banner['ingredientId'] ?? $banner['ingredient_id'] ?? '';
            $ingredient->setIngredientId($ingredientId);
            $image = $banner['image'] ?? '';
            if ($image && !filter_var($image, FILTER_VALIDATE_URL)) {
                // Add the proper path for ingredient images
                $image = $baseUrl . 'formula/homecontent/' . $image;
            }
            $ingredient->setImage($image);
            $koreanIngredientsObjects[] = $ingredient;
        }

        $response->setHeroBanners($heroBanners);
        $response->setFiveStepRoutineBanner($this->getFullImageUrl($homeContent->getFiveStepRoutineBanner(), $baseUrl));
        $response->setThreeStepRoutineBanner($this->getFullImageUrl($homeContent->getThreeStepRoutineBanner(), $baseUrl));
        $response->setDiscoverYourFormulaBanner($this->getFullImageUrl($homeContent->getDiscoverYourFormulaBanner(), $baseUrl));
        $response->setBestOfKoreanFormulaBanner($this->getFullImageUrl($homeContent->getBestOfKoreanFormulaBanner(), $baseUrl));
        $response->setDiscoverKoreanIngredientsBanners($koreanIngredientsObjects);
        $response->setPerfectGiftImage($this->getFullImageUrl($homeContent->getPerfectGiftImage(), $baseUrl));
        $response->setBottomBanner($this->getFullImageUrl($homeContent->getBottomBanner(), $baseUrl));

        return $response;
    }

    public function getAllHomeContentEntities()
    {
        $collection = $this->collectionFactory->create();
        return $collection->getItems();
    }

    protected function getFullImageUrl($imagePath, $baseUrl)
    {
        if (!$imagePath) {
            return '';
        }
        
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }
        
        return $baseUrl . 'formula/homecontent/' . $imagePath;
    }
}