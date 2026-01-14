<?php
namespace Formula\OrderCancellationReturn\Model;

use Formula\OrderCancellationReturn\Api\ReturnImageUploadInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class ReturnImageUpload implements ReturnImageUploadInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Directory path for return images
     */
    const RETURN_IMAGES_DIR = 'returns';

    /**
     * Allowed image mime types
     */
    const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    /**
     * Maximum file size in bytes (5MB)
     */
    const MAX_FILE_SIZE = 5242880;

    public function __construct(
        Filesystem $filesystem,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Upload return images for an order
     *
     * @param int $customerId Customer ID
     * @param int $orderId Order ID
     * @param string[] $images Array of base64 encoded images
     * @return string[] Array of uploaded image paths
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadImages($customerId, $orderId, array $images)
    {
        // Validate order belongs to customer
        $order = $this->orderRepository->get($orderId);
        if ($order->getCustomerId() != $customerId) {
            throw new LocalizedException(__('Order does not belong to this customer.'));
        }

        // Create directory if it doesn't exist
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $returnImagesPath = self::RETURN_IMAGES_DIR . '/' . $orderId;

        if (!$mediaDirectory->isDirectory($returnImagesPath)) {
            $mediaDirectory->create($returnImagesPath);
        }

        $uploadedPaths = [];

        foreach ($images as $index => $base64Image) {
            try {
                // Parse base64 data
                $imageData = $this->parseBase64Image($base64Image);

                // Validate file size
                if (strlen($imageData['content']) > self::MAX_FILE_SIZE) {
                    throw new LocalizedException(__('Image %1 exceeds maximum file size of 5MB.', $index + 1));
                }

                // Generate unique filename
                $filename = sprintf(
                    'return_%d_%d_%s.%s',
                    $orderId,
                    $index + 1,
                    time(),
                    $imageData['extension']
                );

                $filePath = $returnImagesPath . '/' . $filename;

                // Save file
                $mediaDirectory->writeFile($filePath, $imageData['content']);

                $uploadedPaths[] = $filePath;

                $this->logger->info(sprintf(
                    'Return image uploaded: Order %d, File: %s',
                    $orderId,
                    $filePath
                ));

            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'Failed to upload return image %d for order %d: %s',
                    $index + 1,
                    $orderId,
                    $e->getMessage()
                ));
                throw new LocalizedException(
                    __('Failed to upload image %1: %2', $index + 1, $e->getMessage())
                );
            }
        }

        return $uploadedPaths;
    }

    /**
     * Parse base64 encoded image
     *
     * @param string $base64Image Base64 encoded image (with or without data URI prefix)
     * @return array ['content' => binary data, 'extension' => file extension]
     * @throws LocalizedException
     */
    protected function parseBase64Image($base64Image)
    {
        // Check if it has data URI prefix
        if (preg_match('/^data:(image\/\w+);base64,/', $base64Image, $matches)) {
            $mimeType = $matches[1];
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        } else {
            // Try to detect from raw base64
            $decoded = base64_decode($base64Image, true);
            if ($decoded === false) {
                throw new LocalizedException(__('Invalid base64 image data.'));
            }

            // Detect mime type from file signature
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($decoded);
        }

        // Validate mime type
        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new LocalizedException(
                __('Invalid image type. Allowed types: JPEG, PNG, GIF, WebP.')
            );
        }

        $extension = self::ALLOWED_MIME_TYPES[$mimeType];
        $content = isset($decoded) ? $decoded : base64_decode($base64Image, true);

        if ($content === false) {
            throw new LocalizedException(__('Failed to decode base64 image data.'));
        }

        return [
            'content' => $content,
            'extension' => $extension
        ];
    }
}
