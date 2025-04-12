<?php
namespace Formula\Reel\Model;

use Magento\Framework\Exception\LocalizedException;

class VideoUploader
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
        $this->baseTmpPath = "reel/tmp/video";
        $this->basePath = "reel/video";
        $this->allowedExtensions = ['mp4', 'mkv', 'gif'];
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

    public function getFilePath($path, $videoName)
    {
        return rtrim($path, '/') . '/' . ltrim($videoName, '/');
    }

    public function moveFileFromTmp($videoName)
    {
        $baseTmpPath = $this->getBaseTmpPath();
        $basePath = $this->getBasePath();

        $baseVideoPath = $this->getFilePath($basePath, $videoName);
        $baseTmpVideoPath = $this->getFilePath($baseTmpPath, $videoName);

                // Add this at the beginning of important methods like saveFileToTmpDir and moveFileFromTmp
        $logFile = BP . '/var/log/video_uploader.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Starting method: " . __METHOD__ . "\n", FILE_APPEND);
        // Later in the method, log the important variables
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - File data: " . json_encode($_FILES) . "\n", FILE_APPEND);

        try {
            // Create target directory if it doesn't exist
            $targetDir = $this->mediaDirectory->getAbsolutePath($basePath);
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            // Check if temp file exists
            $tmpPath = $this->mediaDirectory->getAbsolutePath($baseTmpPath) . '/' . $videoName;
            if (!file_exists($tmpPath)) {
                throw new LocalizedException(
                    __('Temp file does not exist: %1', $tmpPath)
                );
            }

            $this->coreFileStorageDatabase->copyFile(
                $baseTmpVideoPath,
                $baseVideoPath
            );

            $this->mediaDirectory->renameFile(
                $baseTmpVideoPath,
                $baseVideoPath
            );

            return $videoName;
        } catch (\Exception $e) {
            $this->logger->critical('Error moving file: ' . $e->getMessage());
            throw new LocalizedException(
                __('Something went wrong while saving the file(s). Error: %1', $e->getMessage())
            );
        }
    }

    // Inside the saveFileToTmpDir method of VideoUploader.php, add additional logging
    public function saveFileToTmpDir($fileId)
    {
        try {
            $baseTmpPath = $this->getBaseTmpPath();
            $logFile = BP . '/var/log/video_uploader.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Starting method: " . __METHOD__ . "\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - FileID: " . $fileId . "\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - File data: " . json_encode($_FILES) . "\n", FILE_APPEND);

            // Get the absolute path for the temporary directory
            $tmpPath = $this->mediaDirectory->getAbsolutePath($baseTmpPath);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Temp directory path: " . $tmpPath . "\n", FILE_APPEND);
            
            // Create directory with proper permissions and recursively
            if (!file_exists($tmpPath)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Creating directory: " . $tmpPath . "\n", FILE_APPEND);
                if (!mkdir($tmpPath, 0777, true)) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Failed to create directory: " . $tmpPath . "\n", FILE_APPEND);
                    throw new LocalizedException(
                        __('Failed to create directory: %1', $tmpPath)
                    );
                }
                // Set proper permissions recursively
                chmod($tmpPath, 0777);
            }
            
            // Check if directory is writable
            if (!is_writable($tmpPath)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Directory is not writable: " . $tmpPath . "\n", FILE_APPEND);
                throw new LocalizedException(
                    __('Directory is not writable: %1', $tmpPath)
                );
            }

            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions($this->getAllowedExtensions());
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            // Log before save
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - About to save file to: " . $tmpPath . "\n", FILE_APPEND);
            
            // Try to save the file
            $result = $uploader->save($tmpPath);
            
            // Log result of save operation
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Save result: " . json_encode($result) . "\n", FILE_APPEND);
            
            // If save returned false or empty, handle it
            if (!$result) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Save returned false or empty result\n", FILE_APPEND);
                
                // Instead of throwing exception, create a valid result array if possible
                if (isset($_FILES[$fileId]) && !empty($_FILES[$fileId]['name'])) {
                    $fileName = $_FILES[$fileId]['name'];
                    $tempFile = $_FILES[$fileId]['tmp_name'];
                    $destFile = $tmpPath . '/' . $fileName;
                    
                    // Try a manual copy if the uploader failed
                    if (copy($tempFile, $destFile)) {
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Manual copy succeeded: " . $destFile . "\n", FILE_APPEND);
                        chmod($destFile, 0666);
                        
                        $result = [
                            'file' => $fileName,
                            'tmp_name' => $destFile,
                            'path' => $tmpPath,
                            'url' => $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) 
                                . $this->getFilePath($baseTmpPath, $fileName),
                            'type' => $_FILES[$fileId]['type'],
                            'size' => $_FILES[$fileId]['size'],
                            'name' => $fileName
                        ];
                    } else {
                        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Manual copy failed\n", FILE_APPEND);
                        throw new LocalizedException(
                            __('File could not be saved to temporary directory.')
                        );
                    }
                } else {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - No file data available\n", FILE_APPEND);
                    throw new LocalizedException(
                        __('No file data available.')
                    );
                }
            } else {
                // Process result as before
                $result['tmp_name'] = str_replace('\\', '/', $result['tmp_name']);
                $result['path'] = str_replace('\\', '/', $result['path']);
                $result['url'] = $this->storeManager
                        ->getStore()
                        ->getBaseUrl(
                            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                        ) . $this->getFilePath($baseTmpPath, $result['file']);
                $result['name'] = $result['file'];
            }

            // Try to save file info to database
            if (isset($result['file'])) {
                try {
                    $relativePath = rtrim($baseTmpPath, '/') . '/' . ltrim($result['file'], '/');
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Saving to database: " . $relativePath . "\n", FILE_APPEND);
                    $this->coreFileStorageDatabase->saveFile($relativePath);
                } catch (\Exception $e) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Database save error: " . $e->getMessage() . "\n", FILE_APPEND);
                    // Don't throw here, just log the error
                    $this->logger->critical('Error saving file to database: ' . $e->getMessage());
                }
            }

            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Final result: " . json_encode($result) . "\n", FILE_APPEND);
            return $result;
        } catch (\Exception $e) {
            $logFile = BP . '/var/log/video_uploader.log';
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            
            $this->logger->critical('Error in saveFileToTmpDir: ' . $e->getMessage());
            throw new LocalizedException(
                __('Something went wrong while saving the file(s). Error: %1', $e->getMessage())
            );
        }
    }
}