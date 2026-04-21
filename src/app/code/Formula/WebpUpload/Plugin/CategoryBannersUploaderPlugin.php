<?php
declare(strict_types=1);

namespace Formula\WebpUpload\Plugin;

use Formula\CategoryBanners\Model\BannerImageUploader;
use Formula\WebpUpload\Model\WebpConversionService;

class CategoryBannersUploaderPlugin
{
    private WebpConversionService $conversionService;

    public function __construct(WebpConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    public function afterUploadImage(BannerImageUploader $subject, string $result): string
    {
        try {
            $relativePath = 'formula/categorybanner/' . ltrim($result, '/');
            $this->conversionService->convertFromRelativePath($relativePath);
        } catch (\Throwable $e) {
            // Silently skip — do not break the upload flow
        }
        return $result;
    }
}
