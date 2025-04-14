<?php
// app/code/Formula/CategoryBanners/Ui/Component/Form/CategoryStructure.php
namespace Formula\CategoryBanners\Ui\Component\Form;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\App\Cache\Type\Block as BlockCache;
use Magento\Framework\Serialize\SerializerInterface;

class CategoryStructure implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
    
    /**
     * @var BlockCache
     */
    private $blockCache;
    
    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    /**
     * @var array
     */
    private $categoryPaths = [];

    /**
     * @param CollectionFactory $categoryCollectionFactory
     * @param BlockCache $blockCache
     * @param SerializerInterface $serializer
     */
    public function __construct(
        CollectionFactory $categoryCollectionFactory,
        BlockCache $blockCache,
        SerializerInterface $serializer
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->blockCache = $blockCache;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $cacheKey = 'FORMULA_CATEGORY_BANNER_HIERARCHY';
        $categoriesCache = $this->blockCache->load($cacheKey);
        
        if ($categoriesCache) {
            return $this->serializer->unserialize($categoriesCache);
        }
        
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path', 'level'])
            ->addAttributeToFilter('level', ['gt' => 1]) // Skip root category
            ->addAttributeToFilter('is_active', 1)
            ->setOrder('path', 'ASC')
            ->setOrder('position', 'ASC');
        
        // First pass: build category paths
        foreach ($collection as $category) {
            $this->categoryPaths[$category->getId()] = [
                'id' => $category->getId(),
                'parent_id' => $category->getParentId(),
                'name' => $category->getName(),
                'path' => $category->getPath(),
                'level' => $category->getLevel(),
                'children' => []
            ];
        }
        
        // Second pass: build hierarchical structure
        $options = [];
        
        foreach ($collection as $category) {
            $categoryId = $category->getId();
            $level = $category->getLevel() - 2; // Adjust level for UI display
            $indent = str_repeat('— ', $level);
            
            if ($level > 0) {
                $pathParts = explode('/', $category->getPath());
                $parentNames = [];
                
                // Collect names of parent categories in the path
                for ($i = 2; $i < count($pathParts) - 1; $i++) {
                    $parentId = $pathParts[$i];
                    if (isset($this->categoryPaths[$parentId])) {
                        $parentNames[] = $this->categoryPaths[$parentId]['name'];
                    }
                }
                
                $label = $indent . $category->getName();
            } else {
                $label = $category->getName();
            }
            
            $options[] = [
                'value' => $categoryId,
                'label' => $label,
                '__category_id' => $categoryId,
                '__parent_id' => $category->getParentId(),
                '__level' => $level
            ];
        }
        
        $this->blockCache->save(
            $this->serializer->serialize($options),
            $cacheKey,
            ['catalog_category']
        );
        
        return $options;
    }
}