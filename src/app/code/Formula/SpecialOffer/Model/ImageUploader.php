<?php
declare(strict_types=1);

namespace Formula\SpecialOffer\Model;

use Formula\SpecialOffer\Api\ImageUploaderInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ImageUploader implements ImageUploaderInterface
{
    private const BASE_TMP_PATH = 'specialoffer/tmp';
    private const BASE_PATH = 'specialoffer';
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'gif', 'png', 'webp'];

    private WriteInterface $mediaDirectory;

    public function __construct(
        private readonly Database $coreFileStorageDatabase,
        private readonly Filesystem $filesystem,
        private readonly UploaderFactory $uploaderFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
        $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * @inheritDoc
     */
    public function moveFileFromTmp(string $imageName): string
    {
        $baseTmpPath = self::BASE_TMP_PATH;
        $basePath = self::BASE_PATH;

        $baseImagePath = $this->getFilePath($basePath, $imageName);
        $baseTmpImagePath = $this->getFilePath($baseTmpPath, $imageName);

        try {
            $this->coreFileStorageDatabase->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );
            $this->mediaDirectory->renameFile(
                $baseTmpImagePath,
                $baseImagePath
            );
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new LocalizedException(
                __('Something went wrong while saving the file(s).')
            );
        }

        return $imageName;
    }

    /**
     * @inheritDoc
     */
    public function saveFileToTmpDir(string $fileId): array
    {
        $baseTmpPath = self::BASE_TMP_PATH;

        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions(self::ALLOWED_EXTENSIONS);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);

        $absoluteTmpPath = $this->mediaDirectory->getAbsolutePath($baseTmpPath);
        $result = $uploader->save($absoluteTmpPath);

        if (!$result) {
            throw new LocalizedException(__('File can not be saved to the destination folder.'));
        }

        $result['tmp_name'] = $result['tmp_name'] ?? $result['path'] . '/' . $result['file'];
        $result['path'] = $result['path'] ?? $absoluteTmpPath;
        $result['url'] = $this->getMediaUrl($baseTmpPath . '/' . $result['file']);
        $result['name'] = $result['file'];

        if (isset($result['file'])) {
            try {
                $relativePath = rtrim($baseTmpPath, '/') . '/' . ltrim($result['file'], '/');
                $this->coreFileStorageDatabase->saveFile($relativePath);
            } catch (\Exception $e) {
                $this->logger->critical($e);
                throw new LocalizedException(__('Something went wrong while saving the file(s).'));
            }
        }

        return $result;
    }

    /**
     * Get file path
     */
    private function getFilePath(string $path, string $imageName): string
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }

    /**
     * Get media URL
     */
    private function getMediaUrl(string $path): string
    {
        return $this->storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        ) . $path;
    }

    /**
     * Get base path
     */
    public function getBasePath(): string
    {
        return self::BASE_PATH;
    }

    /**
     * Get base tmp path
     */
    public function getBaseTmpPath(): string
    {
        return self::BASE_TMP_PATH;
    }
}
