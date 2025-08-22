<?php
/**
 * Categories form component for Blog module
 *
 * @category  Formula
 * @package   Formula\Blog
 */
namespace Formula\Blog\Ui\Component\Form;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\App\Cache\Type\Block as BlockCache;
use Magento\Framework\Serialize\SerializerInterface;

class Categories implements OptionSourceInterface
{
    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;
    
    /**
     * @var BlockCache
     */
    protected $blockCache;
    
    /**
     * @var SerializerInterface
     */
    protected $serializer;
    
    /**
     * @var array
     */
    protected $categoryTree;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param BlockCache $blockCache
     * @param SerializerInterface $serializer
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
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
        $cacheKey = 'FORMULA_BLOG_CATEGORY_TREE';
        $categoriesCache = $this->blockCache->load($cacheKey);
        
        if ($categoriesCache) {
            return $this->serializer->unserialize($categoriesCache);
        }
        
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'is_active', 'parent_id'])
            ->addAttributeToFilter('level', ['gt' => 1])
            ->addAttributeToFilter('is_active', 1)
            ->setOrder('position', 'ASC');
        
        $options = [];
        
        foreach ($collection as $category) {
            $options[] = [
                'value' => $category->getId(),
                'label' => $this->buildCategoryLabel($category, $collection),
                'is_active' => $category->getIsActive(),
                'path' => $category->getPath()
            ];
        }
        
        $this->blockCache->save(
            $this->serializer->serialize($options),
            $cacheKey,
            ['catalog_category']
        );
        
        return $options;
    }
    
    /**
     * Build category label with indentation
     *
     * @param \Magento\Catalog\Model\Category $category
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $collection
     * @return string
     */
    protected function buildCategoryLabel($category, $collection)
    {
        $level = $category->getLevel();
        $label = $category->getName();
        
        $indent = str_repeat('—', max(0, $level - 2)) . ' ';
        return $indent . $label;
    }
}
