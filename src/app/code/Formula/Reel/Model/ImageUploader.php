<?php
namespace Formula\Reel\Model;

use Magento\Framework\Exception\LocalizedException;

class ImageUploader
{
    private $coreFileStorageDatabase;
    private $mediaDirectory;
    private $uploaderFactory;
    private $storeManager;
    private $logger;
    public $baseTmpPath;
    public $basePath;
    public $allowedExtensions;

    public function __construct(
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDatabase,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->uploaderFactory = $uploaderFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->baseTmpPath = "reel/tmp/thumbnail";
        $this->basePath = "reel/thumbnail";
        $this->allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    }

    public function setBaseTmpPath($baseTmpPath)
    {
        $this->baseTmpPath = $baseTmpPath;
    }

    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    public function setAllowedExtensions($allowedExtensions)
    {
        $this->allowedExtensions = $allowedExtensions;
    }

    public function getBaseTmpPath()
    {
        return $this->baseTmpPath;
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }

    public function getFilePath($path, $imageName)
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }

    public function moveFileFromTmp($imageName)
    {
        $baseTmpPath = $this->getBaseTmpPath();
        $basePath = $this->getBasePath();

        $baseImagePath = $this->getFilePath($basePath, $imageName);
        $baseTmpImagePath = $this->getFilePath($baseTmpPath, $imageName);

        try {
            $targetDir = $this->mediaDirectory->getAbsolutePath($basePath);
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $tmpPath = $this->mediaDirectory->getAbsolutePath($baseTmpPath) . '/' . $imageName;
            if (!file_exists($tmpPath)) {
                throw new LocalizedException(
                    __('Temp file does not exist: %1', $tmpPath)
                );
            }

            $this->coreFileStorageDatabase->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );

            $this->mediaDirectory->renameFile(
                $baseTmpImagePath,
                $baseImagePath
            );

            return $imageName;
        } catch (\Exception $e) {
            $this->logger->critical('Error moving image file: ' . $e->getMessage());
            throw new LocalizedException(
                __('Something went wrong while saving the image. Error: %1', $e->getMessage())
            );
        }
    }

    public function saveFileToTmpDir($fileId)
    {
        try {
            $baseTmpPath = $this->getBaseTmpPath();
            $tmpPath = $this->mediaDirectory->getAbsolutePath($baseTmpPath);

            if (!file_exists($tmpPath)) {
                if (!mkdir($tmpPath, 0777, true)) {
                    throw new LocalizedException(
                        __('Failed to create directory: %1', $tmpPath)
                    );
                }
                chmod($tmpPath, 0777);
            }

            if (!is_writable($tmpPath)) {
                throw new LocalizedException(
                    __('Directory is not writable: %1', $tmpPath)
                );
            }

            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions($this->getAllowedExtensions());
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $result = $uploader->save($tmpPath);

            if (!$result) {
                throw new LocalizedException(
                    __('File could not be saved to temporary directory.')
                );
            }

            $result['tmp_name'] = str_replace('\\', '/', $result['tmp_name']);
            $result['path'] = str_replace('\\', '/', $result['path']);
            $result['url'] = $this->storeManager
                    ->getStore()
                    ->getBaseUrl(
                        \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                    ) . $this->getFilePath($baseTmpPath, $result['file']);
            $result['name'] = $result['file'];

            if (isset($result['file'])) {
                try {
                    $relativePath = rtrim($baseTmpPath, '/') . '/' . ltrim($result['file'], '/');
                    $this->coreFileStorageDatabase->saveFile($relativePath);
                } catch (\Exception $e) {
                    $this->logger->critical('Error saving image to database: ' . $e->getMessage());
                }
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->critical('Error in saveFileToTmpDir: ' . $e->getMessage());
            throw new LocalizedException(
                __('Something went wrong while saving the image. Error: %1', $e->getMessage())
            );
        }
    }
}
