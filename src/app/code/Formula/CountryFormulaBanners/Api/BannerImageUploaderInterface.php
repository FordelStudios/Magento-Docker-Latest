<?php
// app/code/Formula/CategoryBanners/Api/BannerImageUploaderInterface.php
namespace Formula\CountryFormulaBanners\Api;

/**
 * @api
 */
interface BannerImageUploaderInterface
{
    /**
     * Upload banner image
     *
     * @param string $base64EncodedImage Base64 encoded image content. Can include data URI prefix (e.g., "data:image/jpeg;base64,")
     * @param string|null $fileName Optional filename to use (extension will be added if missing)
     * @return string Image filename that was saved (to be used in banner_image field)
     */
    public function uploadImage($base64EncodedImage, $fileName = null);
}