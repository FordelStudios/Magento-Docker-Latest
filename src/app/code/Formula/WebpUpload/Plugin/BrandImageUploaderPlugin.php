<?php
declare(strict_types=1);

namespace Formula\WebpUpload\Plugin;

use Formula\Brand\Model\ImageUploader;
use Formula\WebpUpload\Model\WebpConversionService;

class BrandImageUploaderPlugin
{
    private WebpConversionService $conversionService;

    public function __construct(WebpConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    public function afterMoveFileFromTmp(ImageUploader $subject, string $result): string
    {
        try {
            $relativePath = rtrim($subject->getBasePath(), '/') . '/' . ltrim($result, '/');
            $this->conversionService->convertFromRelativePath($relativePath);
        } catch (\Throwable $e) {
            // Silently skip — do not break the upload flow
        }
        return $result;
    }
}
