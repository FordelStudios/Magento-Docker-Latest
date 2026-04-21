<?php
declare(strict_types=1);

namespace Formula\WebpUpload\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class WebpConversionService
{
    private $mediaDirectory;

    public function __construct(Filesystem $filesystem)
    {
        $this->mediaDirectory = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
    }

    public function convertFromRelativePath(string $relativePath): void
    {
        $absolutePath = $this->mediaDirectory->getAbsolutePath($relativePath);
        $this->convert($absolutePath);
    }

    public function convert(string $absolutePath): void
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return;
        }

        $image = $this->createImageResource($absolutePath, $ext);
        if ($image === false || $image === null) {
            return;
        }

        $basePath = substr($absolutePath, 0, strrpos($absolutePath, '.'));

        try {
            if (function_exists('imagewebp')) {
                imagewebp($image, $basePath . '.webp', 85);
            }
        } catch (\Throwable $e) {
            // Silently skip
        }

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
     * @return \GdImage|false|null
     */
    private function createImageResource(string $absolutePath, string $ext)
    {
        try {
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    return imagecreatefromjpeg($absolutePath);
                case 'gif':
                    return imagecreatefromgif($absolutePath);
                case 'webp':
                    return imagecreatefromwebp($absolutePath);
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
                    return $image;
                default:
                    return false;
            }
        } catch (\Throwable $e) {
            return false;
        }
    }
}
