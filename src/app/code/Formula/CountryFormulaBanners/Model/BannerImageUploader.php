<?php
// app/code/Formula/CountryFormulaBanners/Model/BannerImageUploader.php
namespace Formula\CountryFormulaBanners\Model;

use Formula\CountryFormulaBanners\Api\BannerImageUploaderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Webapi\Rest\Request;

class BannerImageUploader implements BannerImageUploaderInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var string
     */
    private $bannerImagePath = 'formula/countryformulabanner';

    /**
     * @param Filesystem $filesystem
     * @param File $fileDriver
     */
    public function __construct(
        Filesystem $filesystem,
        File $fileDriver
    ) {
        $this->filesystem = $filesystem;
        $this->fileDriver = $fileDriver;
    }

    /**
     * {@inheritdoc}
     */
    public function uploadImage($base64EncodedImage, $fileName = null)
    {
        try {
            // Check if the base64 string is valid
            if (empty($base64EncodedImage)) {
                throw new LocalizedException(__('Base64 image content is required'));
            }

            // Remove potential data URI prefix (like "data:image/jpeg;base64,")
            if (strpos($base64EncodedImage, ';base64,') !== false) {
                list(, $base64EncodedImage) = explode(';base64,', $base64EncodedImage);
            }
            
            // Remove any whitespace
            $base64EncodedImage = trim($base64EncodedImage);

            // Decode the base64 string
            $imageContent = base64_decode($base64EncodedImage, true);
            if ($imageContent === false) {
                throw new LocalizedException(__('Invalid base64 image content'));
            }

            // Detect file type from binary data (optional - you could skip this)
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);
            
            // Determine file extension from MIME type
            $extension = $this->getExtensionFromMimeType($mimeType);
            
            // Generate filename if not provided
            if (empty($fileName)) {
                $fileName = uniqid('banner_') . '.' . $extension;
            } else {
                // Make sure filename has an extension
                if (!preg_match('/\.(jpg|jpeg|png|gif)$/i', $fileName)) {
                    $fileName .= '.' . $extension;
                }
            }

            // Get media directory
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            
            // Create target directory if it doesn't exist
            $targetDir = $this->bannerImagePath;
            if (!$mediaDirectory->isExist($targetDir)) {
                $mediaDirectory->create($targetDir);
            }

            // Full path to save the file
            $filePath = $targetDir . '/' . $fileName;

            // Save the file
            $mediaDirectory->writeFile($filePath, $imageContent);

            return $fileName;
        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not save image: %1', $e->getMessage()));
        }
    }
    
    /**
     * Get file extension from MIME type
     *
     * @param string $mimeType
     * @return string
     */
    private function getExtensionFromMimeType($mimeType)
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];
        
        return isset($map[$mimeType]) ? $map[$mimeType] : 'jpg';
    }
}