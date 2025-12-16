<?php
namespace Formula\Reel\Controller\Adminhtml\Reel;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Formula\Reel\Model\ReelFactory;
use Formula\Reel\Model\ReelRepository;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Formula\Reel\Model\VideoUploader;
use Formula\Reel\Model\ImageUploader;
use getID3;

class Save extends Action
{
    protected $dataPersistor;
    protected $reelFactory;
    protected $reelRepository;
    protected $logger;
    protected $uploaderFactory;
    protected $filesystem;
    protected $videoUploader;
    protected $imageUploader;
    protected $mediaDirectory;

    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        ReelFactory $reelFactory,
        ReelRepository $reelRepository,
        LoggerInterface $logger,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        VideoUploader $videoUploader,
        ImageUploader $imageUploader
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->reelFactory = $reelFactory;
        $this->reelRepository = $reelRepository;
        $this->logger = $logger;
        $this->uploaderFactory = $uploaderFactory;
        $this->videoUploader = $videoUploader;
        $this->imageUploader = $imageUploader;
        $this->filesystem = $filesystem;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        parent::__construct($context);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_Reel::reel');
    }



    protected function getVideoDuration($videoPath)
    {
        // Add debug logging
        file_put_contents(BP . '/var/log/video-debug.log', 
            date('Y-m-d H:i:s') . " - Checking video: $videoPath\n", 
            FILE_APPEND);
        
        try {
            // Check file exists
            if (!file_exists($videoPath)) {
                file_put_contents(BP . '/var/log/video-debug.log', 
                    date('Y-m-d H:i:s') . " - File does not exist\n", 
                    FILE_APPEND);
                return 0; // Return 0 instead of 'Unknown'
            }
            
            // Try getID3
            $getID3Path = BP . '/vendor/james-heinrich/getid3/getid3/getid3.php';
            if (file_exists($getID3Path)) {
                require_once $getID3Path;
                
                $getID3 = new \getID3();
                $fileInfo = $getID3->analyze($videoPath);
                
                file_put_contents(BP . '/var/log/video-debug.log', 
                    date('Y-m-d H:i:s') . " - getID3 analysis keys: " . 
                    implode(', ', array_keys($fileInfo)) . "\n", 
                    FILE_APPEND);
                
                if (isset($fileInfo['playtime_seconds'])) {
                    // Log the actual value
                    file_put_contents(BP . '/var/log/video-debug.log', 
                        date('Y-m-d H:i:s') . " - Raw playtime_seconds value: " . $fileInfo['playtime_seconds'] . "\n", 
                        FILE_APPEND);
                    
                    // Return the raw seconds as an integer
                    return (int)$fileInfo['playtime_seconds'];
                } 
                // Try to extract duration from quicktime container
                elseif (isset($fileInfo['quicktime']) && isset($fileInfo['quicktime']['moov'])) {
                    file_put_contents(BP . '/var/log/video-debug.log', 
                        date('Y-m-d H:i:s') . " - Attempting to extract duration from quicktime container\n", 
                        FILE_APPEND);
                        
                    // Try tkhd (track header) approach
                    if (isset($fileInfo['quicktime']['moov']['trak']['tkhd']['duration']) && 
                        isset($fileInfo['quicktime']['moov']['mvhd']['time_scale'])) {
                        $duration = $fileInfo['quicktime']['moov']['trak']['tkhd']['duration'];
                        $timeScale = $fileInfo['quicktime']['moov']['mvhd']['time_scale'];
                        if ($timeScale > 0) {
                            $seconds = $duration / $timeScale;
                            file_put_contents(BP . '/var/log/video-debug.log', 
                                date('Y-m-d H:i:s') . " - Extracted duration from tkhd: $seconds\n", 
                                FILE_APPEND);
                            return (int)$seconds;
                        }
                    }
                    
                    // Try mvhd (movie header) approach
                    if (isset($fileInfo['quicktime']['moov']['mvhd']['duration']) && 
                        isset($fileInfo['quicktime']['moov']['mvhd']['time_scale'])) {
                        $duration = $fileInfo['quicktime']['moov']['mvhd']['duration'];
                        $timeScale = $fileInfo['quicktime']['moov']['mvhd']['time_scale'];
                        if ($timeScale > 0) {
                            $seconds = $duration / $timeScale;
                            file_put_contents(BP . '/var/log/video-debug.log', 
                                date('Y-m-d H:i:s') . " - Extracted duration from mvhd: $seconds\n", 
                                FILE_APPEND);
                            return (int)$seconds;
                        }
                    }
                }
            } else {
                file_put_contents(BP . '/var/log/video-debug.log', 
                    date('Y-m-d H:i:s') . " - getID3 not found at $getID3Path\n", 
                    FILE_APPEND);
            }
            
            // Fallback to ffprobe
            file_put_contents(BP . '/var/log/video-debug.log', 
                date('Y-m-d H:i:s') . " - Trying ffprobe\n", 
                FILE_APPEND);
                
            // Fallback to existing ffprobe method if getID3 fails
            $ffmpegPath = shell_exec('which ffprobe');
            $ffmpegPath = ($ffmpegPath !== null) ? trim($ffmpegPath) : '';
        
            if (!empty($ffmpegPath)) {
                $command = "$ffmpegPath -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($videoPath);
                $duration = trim(shell_exec($command));
                
                if (is_numeric($duration)) {
                    return (int)$duration;
                }
            }
            
        } catch (\Exception $e) {
            file_put_contents(BP . '/var/log/video-debug.log', 
                date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", 
                FILE_APPEND);
        }
        
        file_put_contents(BP . '/var/log/video-debug.log',
            date('Y-m-d H:i:s') . " - Returning 0\n",
            FILE_APPEND);

        return 0; // Return 0 instead of 'Unknown'
    }

    /**
     * Generate thumbnail from video using ffmpeg
     *
     * @param string $videoPath
     * @param string $videoName
     * @return string|null
     */
    protected function generateThumbnailFromVideo($videoPath, $videoName)
    {
        try {
            $ffmpegPath = trim(shell_exec('which ffmpeg') ?? '');
            if (empty($ffmpegPath)) {
                $this->logger->warning('ffmpeg not found, cannot generate thumbnail');
                return null;
            }

            // Create thumbnail directory if not exists
            $thumbnailDir = $this->mediaDirectory->getAbsolutePath('reel/thumbnail');
            if (!file_exists($thumbnailDir)) {
                mkdir($thumbnailDir, 0777, true);
            }

            // Generate thumbnail filename based on video name
            $thumbnailName = pathinfo($videoName, PATHINFO_FILENAME) . '_thumb.jpg';
            $thumbnailPath = $thumbnailDir . '/' . $thumbnailName;

            // Extract frame at 1 second mark (or first frame if video is shorter)
            $command = sprintf(
                '%s -i %s -ss 00:00:01 -vframes 1 -q:v 2 %s -y 2>&1',
                $ffmpegPath,
                escapeshellarg($videoPath),
                escapeshellarg($thumbnailPath)
            );

            $output = shell_exec($command);
            $this->logger->debug('ffmpeg thumbnail command output: ' . $output);

            if (file_exists($thumbnailPath)) {
                $this->logger->debug('Thumbnail generated successfully: ' . $thumbnailName);
                return $thumbnailName;
            }

            $this->logger->warning('Thumbnail file was not created');
            return null;
        } catch (\Exception $e) {
            $this->logger->critical('Error generating thumbnail: ' . $e->getMessage());
            return null;
        }
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        $this->logger->debug('Reel Save Controller: Post data', ['data' => $data]);

        if (isset($_FILES['video']) && !empty($_FILES['video']['name']) && (!isset($data['video']) || empty($data['video']))) {
            // File was selected but not properly handled by the form
            try {
                $fileName = $_FILES['video']['name'];
                $this->logger->debug('Detected file upload not in form data: ' . $fileName);
                
                // Create an uploader manually
                $uploader = $this->uploaderFactory->create(['fileId' => 'video']);
                $uploader->setAllowedExtensions(['mp4', 'mkv', 'gif']);
                $uploader->setAllowRenameFiles(true);
                
                // Save to the proper directory
                $targetDir = $this->mediaDirectory->getAbsolutePath('reel/video');
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                $result = $uploader->save($targetDir);
                
                if (isset($result['file'])) {
                    // Add the file to the data array
                    $data['video'] = $result['file'];
                    $data['timer'] = '00:30'; // Default timer value
                    $this->logger->debug('Successfully processed file from direct upload: ' . $result['file']);
                }
            } catch (\Exception $e) {
                $this->logger->critical('Error processing direct file upload: ' . $e->getMessage());
            }
        }
        
        if ($data) {
            $id = isset($data['reel_id']) ? $data['reel_id'] : null;
            
            if (empty($id)) {
                $data['reel_id'] = null;
                $model = $this->reelFactory->create();
            } else {
                try {
                    $model = $this->reelRepository->getById($id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This reel no longer exists.'));
                    $this->logger->critical('Reel save error: ' . $e->getMessage());
                    return $resultRedirect->setPath('*/*/');
                }
            }
            
            // Handle video upload
            $this->logger->debug('Video data:', isset($data['video']) ? ['video' => $data['video']] : ['video' => 'not set']);
            
            if (isset($data['video']) && is_array($data['video'])) {
                if (!empty($data['video'][0]['name'])) {
                    try {
                        $videoName = $data['video'][0]['name'];
                        $this->logger->debug('Processing video: ' . $videoName);
                        
                        // Check if this is a new upload or an existing file
                        if (isset($data['video'][0]['tmp_name']) && !empty($data['video'][0]['tmp_name'])) {
                            $this->logger->debug('New upload detected, moving from tmp');
                            $this->videoUploader->setBaseTmpPath("reel/tmp/video");
                            $this->videoUploader->setBasePath("reel/video");
                            $this->videoUploader->moveFileFromTmp($videoName);
                        }
                        
                        // Set video name to save in database
                        $data['video'] = $videoName;
                        
                        $videoPath = $this->mediaDirectory->getAbsolutePath('reel/video/' . $videoName);
                        if (file_exists($videoPath)) {
                            $data['timer'] = $this->getVideoDuration($videoPath);
                        } else {
                            $data['timer'] = '00:30'; // Default as fallback
                        }
                        
                        $this->logger->debug('Video successfully processed: ' . $videoName);
                    } catch (\Exception $e) {
                        $this->logger->critical('Error processing video: ' . $e->getMessage());
                        $this->messageManager->addExceptionMessage($e, __('Error processing video upload: %1', $e->getMessage()));
                    }
                } else {
                    $this->logger->debug('No video name found in data.');
                    if (empty($model->getVideo())) {
                        $this->messageManager->addErrorMessage(__('Video is required. Please upload a video.'));
                        $this->dataPersistor->set('reel', $data);
                        return $resultRedirect->setPath('*/*/edit', ['reel_id' => $id]);
                    }
                }
            } else {
                $this->logger->debug('No video data found in request.');
                if (empty($id) || empty($model->getVideo())) {
                    $this->messageManager->addErrorMessage(__('Video is required. Please upload a video.'));
                    $this->dataPersistor->set('reel', $data);
                    return $resultRedirect->setPath('*/*/edit', ['reel_id' => $id]);
                }
            }
            
            // Handle thumbnail upload or auto-generation
            $manualThumbnail = false;
            if (isset($data['thumbnail']) && is_array($data['thumbnail'])) {
                if (!empty($data['thumbnail'][0]['name'])) {
                    try {
                        $thumbnailName = $data['thumbnail'][0]['name'];
                        $this->logger->debug('Processing manual thumbnail: ' . $thumbnailName);

                        // Check if this is a new upload or an existing file
                        if (isset($data['thumbnail'][0]['tmp_name']) && !empty($data['thumbnail'][0]['tmp_name'])) {
                            $this->logger->debug('New thumbnail upload detected, moving from tmp');
                            $this->imageUploader->setBaseTmpPath("reel/tmp/thumbnail");
                            $this->imageUploader->setBasePath("reel/thumbnail");
                            $this->imageUploader->moveFileFromTmp($thumbnailName);
                        }

                        $data['thumbnail'] = $thumbnailName;
                        $manualThumbnail = true;
                        $this->logger->debug('Thumbnail successfully processed: ' . $thumbnailName);
                    } catch (\Exception $e) {
                        $this->logger->critical('Error processing thumbnail: ' . $e->getMessage());
                    }
                } else {
                    // Empty array means thumbnail was removed
                    $data['thumbnail'] = null;
                }
            } elseif (!isset($data['thumbnail']) || empty($data['thumbnail'])) {
                // No manual thumbnail - keep existing or set to null for auto-generation
                $data['thumbnail'] = null;
            }

            // Auto-generate thumbnail from video if no manual thumbnail was provided
            if (!$manualThumbnail && isset($data['video']) && !empty($data['video']) && !is_array($data['video'])) {
                $videoPath = $this->mediaDirectory->getAbsolutePath('reel/video/' . $data['video']);
                if (file_exists($videoPath)) {
                    $generatedThumbnail = $this->generateThumbnailFromVideo($videoPath, $data['video']);
                    if ($generatedThumbnail) {
                        $data['thumbnail'] = $generatedThumbnail;
                        $this->logger->debug('Auto-generated thumbnail: ' . $generatedThumbnail);
                    }
                }
            }

            // Handle product_ids if it's an array
            if (isset($data['product_ids']) && is_array($data['product_ids'])) {
                $data['product_ids'] = implode(',', $data['product_ids']);
            }

            // Handle category_ids if it's an array
            if (isset($data['category_ids']) && is_array($data['category_ids'])) {
                $data['category_ids'] = implode(',', $data['category_ids']);
            }

            $this->logger->debug('Final data before saving:', ['data' => $data]);
            $model->setData($data);

            try {
                $this->reelRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the reel post.'));
                $this->dataPersistor->clear('reel');
                
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['reel_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->logger->critical('Reel save error: ' . $e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the reel post.'));
                $this->logger->critical('Reel save error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            }
            
            $this->dataPersistor->set('reel', $data);
            return $resultRedirect->setPath('*/*/edit', ['reel_id' => $id]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}