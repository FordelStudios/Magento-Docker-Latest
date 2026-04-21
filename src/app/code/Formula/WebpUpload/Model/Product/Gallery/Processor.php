<?php
declare(strict_types=1);

namespace Formula\WebpUpload\Model\Product\Gallery;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterface as ImageContentInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\Uploader;

class Processor extends \Magento\Catalog\Model\Product\Gallery\Processor
{
    /**
     * Adds webp to the allowed image extensions for product gallery uploads.
     */
    public function addImage(
        \Magento\Catalog\Model\Product $product,
        $file,
        $mediaAttribute = null,
        $move = false,
        $exclude = true
    ) {
        $file = $this->mediaDirectory->getRelativePath($file);
        if (!$this->mediaDirectory->isFile($file)) {
            throw new LocalizedException(__("The image doesn't exist."));
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $pathinfo = pathinfo($file);
        $imgExtensions = ['jpg', 'jpeg', 'gif', 'png', 'webp'];
        if (!isset($pathinfo['extension']) || !in_array(strtolower($pathinfo['extension']), $imgExtensions)) {
            throw new LocalizedException(
                __('The image type for the file is invalid. Enter the correct image type and try again.')
            );
        }

        $fileName = Uploader::getCorrectFileName($pathinfo['basename']);
        $dispersionPath = Uploader::getDispersionPath($fileName);
        $fileName = $dispersionPath . '/' . $fileName;

        $fileName = $this->getNotDuplicatedFilename($fileName, $dispersionPath);

        $destinationFile = $this->mediaConfig->getTmpMediaPath($fileName);

        try {
            $storageHelper = $this->fileStorageDb;
            if ($move) {
                $this->mediaDirectory->renameFile($file, $destinationFile);
                $storageHelper->saveFile($this->mediaConfig->getTmpMediaShortUrl($fileName));
            } else {
                $this->mediaDirectory->copyFile($file, $destinationFile);
                $storageHelper->saveFile($this->mediaConfig->getTmpMediaShortUrl($fileName));
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('The "%1" file couldn\'t be moved.', $e->getMessage()));
        }

        $fileName = str_replace('\\', '/', $fileName);

        $attrCode = $this->getAttribute()->getAttributeCode();
        $mediaGalleryData = $product->getData($attrCode);
        $position = 0;

        $absoluteFilePath = $this->mediaDirectory->getAbsolutePath($destinationFile);
        $this->generateModernFormats($absoluteFilePath);
        $imageMimeType = $this->mime->getMimeType($absoluteFilePath);
        $imageContent = $this->mediaDirectory->readFile($absoluteFilePath);
        $imageBase64 = base64_encode($imageContent);
        $imageName = $pathinfo['filename'];

        if (!is_array($mediaGalleryData)) {
            $mediaGalleryData = ['images' => []];
        }

        foreach ($mediaGalleryData['images'] as &$image) {
            if (isset($image['position']) && $image['position'] > $position) {
                $position = $image['position'];
            }
        }

        $position++;
        $mediaGalleryData['images'][] = [
            'file' => $fileName,
            'position' => $position,
            'label' => '',
            'disabled' => (int)$exclude,
            'media_type' => 'image',
            'types' => $mediaAttribute,
            'content' => [
                'data' => [
                    ImageContentInterface::NAME => $imageName,
                    ImageContentInterface::BASE64_ENCODED_DATA => $imageBase64,
                    ImageContentInterface::TYPE => $imageMimeType,
                ]
            ]
        ];

        $product->setData($attrCode, $mediaGalleryData);

        if ($mediaAttribute !== null) {
            $this->setMediaAttribute($product, $mediaAttribute, $fileName);
        }

        return $fileName;
    }

    /**
     * Generates AVIF and WebP versions of the uploaded image alongside the original.
     * Silently skips on any failure to avoid breaking the upload flow.
     */
    private function generateModernFormats(string $absolutePath): void
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $supported = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $supported, true)) {
            return;
        }

        $image = $this->createImageResource($absolutePath, $ext);
        if ($image === false || $image === null) {
            return;
        }

        $basePath = substr($absolutePath, 0, strrpos($absolutePath, '.'));

        // Generate WebP (quality 85)
        try {
            if (function_exists('imagewebp')) {
                imagewebp($image, $basePath . '.webp', 85);
            }
        } catch (\Throwable $e) {
            // Silently skip
        }

        // Generate AVIF (quality 75) — guard for containers without libavif yet
        try {
            if (function_exists('imageavif')) {
                imageavif($image, $basePath . '.avif', 75);
            }
        } catch (\Throwable $e) {
            // Silently skip
        }

        imagedestroy($image);
    }

    /**
     * Creates a GD image resource from a file path.
     * Handles PNG transparency correctly.
     *
     * @return \GdImage|false|null
     */
    private function createImageResource(string $absolutePath, string $ext)
    {
        try {
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($absolutePath);
                    break;
                case 'png':
                    $src = imagecreatefrompng($absolutePath);
                    if ($src === false) {
                        return false;
                    }
                    $w = imagesx($src);
                    $h = imagesy($src);
                    $image = imagecreatetruecolor($w, $h);
                    if ($image === false) {
                        imagedestroy($src);
                        return false;
                    }
                    imagepalettetotruecolor($image);
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
                    imagefilledrectangle($image, 0, 0, $w, $h, $transparent);
                    imagealphablending($image, true);
                    imagecopy($image, $src, 0, 0, 0, 0, $w, $h);
                    imagedestroy($src);
                    break;
                case 'gif':
                    $image = imagecreatefromgif($absolutePath);
                    break;
                case 'webp':
                    $image = imagecreatefromwebp($absolutePath);
                    break;
                default:
                    return false;
            }
            return $image;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
