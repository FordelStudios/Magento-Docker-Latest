<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Api;

interface ImageUploaderInterface
{
    /**
     * Move file from temporary directory to permanent storage
     *
     * @param string $imageName
     * @return string
     */
    public function moveFileFromTmp(string $imageName): string;

    /**
     * Save file to temporary directory
     *
     * @param string $fileId
     * @return array
     */
    public function saveFileToTmpDir(string $fileId): array;
}
