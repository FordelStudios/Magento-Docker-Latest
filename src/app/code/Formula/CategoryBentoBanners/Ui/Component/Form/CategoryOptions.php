<?php
namespace Formula\CategoryBentoBanners\Ui\Component\Form;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class CategoryOptions implements OptionSourceInterface
{
    protected $categoryCollectionFactory;

    public function __construct(CategoryCollectionFactory $categoryCollectionFactory)
    {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'parent_id'])
                   ->addFieldToFilter('parent_id', 2)  // Only categories with parent_id = 2
                   ->addFieldToFilter('is_active', 1)
                   ->setOrder('name', 'ASC');

        $options = [['value' => '', 'label' => __('-- Please select a category --')]];

        foreach ($collection as $category) {
            $options[] = [
                'value' => $category->getId(),
                'label' => $category->getName()
            ];
        }

        return $options;
    }
}