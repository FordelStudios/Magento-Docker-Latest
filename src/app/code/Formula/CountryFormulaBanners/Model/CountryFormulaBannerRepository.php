<?php
// app/code/Formula/CountryFormulaBanners/Model/CountryFormulaBannerRepository.php
namespace Formula\CountryFormulaBanners\Model;

use Formula\CountryFormulaBanners\Api\CountryFormulaBannerRepositoryInterface;
use Formula\CountryFormulaBanners\Api\Data\CountryFormulaBannerInterface;
use Formula\CountryFormulaBanners\Model\ResourceModel\CountryFormulaBanner as ResourceCountryFormulaBanner;
use Formula\CountryFormulaBanners\Model\ResourceModel\CountryFormulaBanner\CollectionFactory as CountryFormulaBannerCollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class CountryFormulaBannerRepository implements CountryFormulaBannerRepositoryInterface
{
    /**
     * @var ResourceCountryFormulaBanner
     */
    private $resource;

    /**
     * @var CountryFormulaBannerFactory
     */
    private $countryFormulaBannerFactory;

    /**
     * @var CountryFormulaBannerCollectionFactory
     */
    private $countryFormulaBannerCollectionFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param ResourceCountryFormulaBanner $resource
     * @param CountryFormulaBannerFactory $countryFormulaBannerFactory
     * @param CountryFormulaBannerCollectionFactory $countryFormulaBannerCollectionFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        ResourceCountryFormulaBanner $resource,
        CountryFormulaBannerFactory $countryFormulaBannerFactory,
        CountryFormulaBannerCollectionFactory $countryFormulaBannerCollectionFactory,
        DateTime $dateTime
    ) {
        $this->resource = $resource;
        $this->countryFormulaBannerFactory = $countryFormulaBannerFactory;
        $this->countryFormulaBannerCollectionFactory = $countryFormulaBannerCollectionFactory;
        $this->dateTime = $dateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CountryFormulaBannerInterface $banner)
    {
        try {
            // Ensure timestamps are set correctly
            if ($banner->getId()) {
                $banner->setUpdatedAt($this->dateTime->gmtDate());
            } else {
                $now = $this->dateTime->gmtDate();
                $banner->setCreatedAt($now);
                $banner->setUpdatedAt($now);
            }
            
            $this->resource->save($banner);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $banner;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id)
    {
        $banner = $this->countryFormulaBannerFactory->create();
        $this->resource->load($banner, $id);
        if (!$banner->getId()) {
            throw new NoSuchEntityException(__('The country formula banner with the "%1" ID doesn\'t exist.', $id));
        }
        return $banner;
    }

    /**
     * {@inheritdoc}
     */
    public function getByCountry($countryId)
    {
        // Validate country ID format (should be 2-letter ISO code)
        if (empty($countryId) || strlen($countryId) !== 2) {
            throw new NoSuchEntityException(__('Invalid country ID format. Expected 2-letter ISO code, got: %1', $countryId));
        }

        $collection = $this->countryFormulaBannerCollectionFactory->create();
        $collection->addFieldToFilter('country_id', $countryId);
        $collection->addFieldToFilter('is_active', 1);
        
        return $collection->getItems();
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
    {
        $collection = $this->countryFormulaBannerCollectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        return $collection->getItems();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(CountryFormulaBannerInterface $banner)
    {
        try {
            $this->resource->delete($banner);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }
}