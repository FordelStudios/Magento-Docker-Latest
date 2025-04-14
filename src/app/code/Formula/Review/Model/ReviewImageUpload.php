<?php
namespace Formula\Review\Model;

use Formula\Review\Api\ReviewImageUploadInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Webapi\Rest\Request;

class ReviewImageUpload implements ReviewImageUploadInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;
    
    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;
    
    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param Request $request
     */
    public function __construct(
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        Request $request
    ) {
        $this->filesystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function upload()
    {
        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $target = $mediaDirectory->getAbsolutePath('review_images');
            
            // Create target directory if it doesn't exist
            if (!file_exists($target)) {
                mkdir($target, 0777, true);
            }
            
            // Get file from the REST request
            $files = $this->request->getFiles()->toArray();
            if (empty($files['image'])) {
                throw new \Exception('No file uploaded');
            }
            
            // Validate and upload file
            $uploader = $this->uploaderFactory->create(['fileId' => 'image']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            
            $savedFile = $uploader->save($target);
            
            // Return file path relative to MEDIA directory
            return [
                'success' => true,
                'file' => 'review_images/' . $savedFile['file']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}