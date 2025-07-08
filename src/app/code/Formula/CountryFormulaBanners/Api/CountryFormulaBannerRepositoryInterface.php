<?php
// app/code/Formula/CountryFormulaBanners/Api/CountryFormulaBannerRepositoryInterface.php
namespace Formula\CountryFormulaBanners\Api;

use Formula\CountryFormulaBanners\Api\Data\CountryFormulaBannerInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @api
 */
interface CountryFormulaBannerRepositoryInterface
{
    /**
     * Save banner
     *
     * @param CountryFormulaBannerInterface $banner
     * @return CountryFormulaBannerInterface
     * @throws CouldNotSaveException
     */
    public function save(CountryFormulaBannerInterface $banner);

    /**
     * Get banner by ID
     *
     * @param int $id
     * @return CountryFormulaBannerInterface
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * Get banners by country
     *
     * @param string $countryId  // Changed from int to string
     * @return CountryFormulaBannerInterface[]
     */
    public function getByCountry($countryId);  // Changed parameter name for clarity

    /**
     * Delete banner
     *
     * @param CountryFormulaBannerInterface $banner
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(CountryFormulaBannerInterface $banner);

    /**
     * Delete banner by ID
     *
     * @param int $id
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById($id);
}