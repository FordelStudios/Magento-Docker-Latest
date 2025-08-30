<?php
declare(strict_types=1);

namespace Formula\HomeContent\Controller\Adminhtml\HomeContent;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Formula\HomeContent\Api\HomeContentRepositoryInterface;
use Formula\HomeContent\Model\HomeContentFactory;
use Formula\HomeContent\Model\ResourceModel\HomeContent\CollectionFactory;
use Formula\HomeContent\Model\ImageUploader;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class Save extends Action implements HttpPostActionInterface
{
    protected $homeContentRepository;
    protected $homeContentFactory;
    protected $collectionFactory;
    protected $imageUploader;
    protected $jsonSerializer;
    protected $logger;

    public function __construct(
        Context $context,
        HomeContentRepositoryInterface $homeContentRepository,
        HomeContentFactory $homeContentFactory,
        CollectionFactory $collectionFactory,
        ImageUploader $imageUploader,
        Json $jsonSerializer,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->homeContentRepository = $homeContentRepository;
        $this->homeContentFactory = $homeContentFactory;
        $this->collectionFactory = $collectionFactory;
        $this->imageUploader = $imageUploader;
        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        
        try {
            $data = $this->getRequest()->getPostValue();
            
            // Debug: Log the raw POST data
            $this->logger->debug('Save Controller - Raw POST data: ' . json_encode($data));
            
            if (!$data) {
                $this->messageManager->addError(__('No data to save'));
                return $resultRedirect->setPath('*/*/index');
            }

            $id = $this->getRequest()->getParam('id');
            $homeContent = $this->homeContentFactory->create();
            
            if ($id) {
                $homeContent->load($id);
                if (!$homeContent->getEntityId()) {
                    $this->messageManager->addError(__('This home content no longer exists.'));
                    return $resultRedirect->setPath('*/*/index');
                }
            }

            $data = $this->processImageUploads($data);

            // Debug: Log data after image processing
            $this->logger->debug('Save Controller - Data after image processing: ' . json_encode($data));

            // Handle active field - work with 0/1 values
            $activeValue = isset($data['active']) ? (int)$data['active'] : 0;
            
            // Debug: Log active field processing
            $this->logger->debug('Save Controller - Active field processing:');
            $this->logger->debug('Save Controller - Raw active value: ' . var_export($data['active'] ?? 'NOT_SET', true));
            $this->logger->debug('Save Controller - Processed active value: ' . $activeValue);
            $this->logger->debug('Save Controller - Active value type: ' . gettype($activeValue));
            
            $homeContent->setActive($activeValue);

            $homeContent->setData($data);
            
            // Debug: Log the data being saved
            $this->logger->debug('Save Controller - Final data being saved: ' . json_encode($homeContent->getData()));
            
            $homeContent->save();
            
            // Debug: Log the saved entity data
            $this->logger->debug('Save Controller - Entity saved with ID: ' . $homeContent->getEntityId());
            $this->logger->debug('Save Controller - Saved active value: ' . $homeContent->getActive());

            $this->messageManager->addSuccess(__('HomePage Content has been saved.'));
            
            // Redirect to listing page on successful save
            return $resultRedirect->setPath('*/*/index');
            
        } catch (\Exception $e) {
            $this->logger->error('Save Controller - Exception: ' . $e->getMessage());
            $this->logger->error('Save Controller - Exception trace: ' . $e->getTraceAsString());
            
            $this->messageManager->addError($e->getMessage());
            
            // Stay on the form page (edit) if there's an error
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            } else {
                return $resultRedirect->setPath('*/*/edit');
            }
        }
    }

    protected function processImageUploads($data)
    {
        $imageFields = [
            'five_step_routine_banner',
            'three_step_routine_banner',
            'discover_your_formula_banner',
            'best_of_korean_formula_banner',
            'perfect_gift_image',
            'bottom_banner'
        ];

        foreach ($imageFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                if (!empty($data[$field][0]['name']) && !empty($data[$field][0]['tmp_name'])) {
                    try {
                        // Move file from tmp to final location
                        $data[$field] = $data[$field][0]['name'];
                        $this->imageUploader->moveFileFromTmp($data[$field]);
                    } catch (\Exception $e) {
                        // If move fails, keep the filename but log the error
                        $data[$field] = $data[$field][0]['name'];
                        $this->logger->error("Failed to move file {$data[$field]}: " . $e->getMessage());
                    }
                } elseif (!empty($data[$field][0]['name']) && empty($data[$field][0]['tmp_name'])) {
                    // File already exists, just use the name
                    $data[$field] = $data[$field][0]['name'];
                } else {
                    unset($data[$field]);
                }
            }
        }

        // Handle hero banners
        if (isset($data['hero_banners']) && is_array($data['hero_banners'])) {
            $heroBanners = [];
            foreach ($data['hero_banners'] as $banner) {
                if (!empty($banner['name']) && !empty($banner['tmp_name'])) {
                    try {
                        $this->imageUploader->moveFileFromTmp($banner['name']);
                        $heroBanners[] = $banner['name'];
                    } catch (\Exception $e) {
                        $heroBanners[] = $banner['name'];
                        $this->logger->error("Failed to move hero banner {$banner['name']}: " . $e->getMessage());
                    }
                } elseif (!empty($banner['name']) && empty($banner['tmp_name'])) {
                    $heroBanners[] = $banner['name'];
                }
            }
            if (!empty($heroBanners)) {
                $data['hero_banners'] = $this->jsonSerializer->serialize($heroBanners);
            } else {
                unset($data['hero_banners']);
            }
        }

        // Handle Korean ingredients banners
        if (isset($data['discover_korean_ingredients_banners']) && is_array($data['discover_korean_ingredients_banners'])) {
            $koreanBanners = [];
            foreach ($data['discover_korean_ingredients_banners'] as $banner) {
                if (isset($banner['ingredient_id']) && !empty($banner['ingredient_id'])) {
                    $imageName = '';
                    if (isset($banner['image']) && is_array($banner['image'])) {
                        if (!empty($banner['image'][0]['name']) && !empty($banner['image'][0]['tmp_name'])) {
                            try {
                                $imageName = $banner['image'][0]['name'];
                                $this->imageUploader->moveFileFromTmp($imageName);
                            } catch (\Exception $e) {
                                $imageName = $banner['image'][0]['name'];
                                $this->logger->error("Failed to move Korean banner {$imageName}: " . $e->getMessage());
                            }
                        } elseif (!empty($banner['image'][0]['name'])) {
                            $imageName = $banner['image'][0]['name'];
                        }
                    } elseif (isset($banner['image']) && is_string($banner['image'])) {
                        $imageName = $banner['image'];
                    }
                    
                    $koreanBanners[] = [
                        'image' => $imageName,
                        'ingredientId' => (int)$banner['ingredient_id']
                    ];
                }
            }
            if (!empty($koreanBanners)) {
                $data['discover_korean_ingredients_banners'] = $this->jsonSerializer->serialize($koreanBanners);
            } else {
                unset($data['discover_korean_ingredients_banners']);
            }
        }

        return $data;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Formula_HomeContent::home_content_manage');
    }
}