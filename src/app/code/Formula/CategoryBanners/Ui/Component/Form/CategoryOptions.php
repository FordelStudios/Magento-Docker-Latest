<?php
// app/code/Formula/CategoryBanners/Ui/Component/Form/CategoryOptions.php
namespace Formula\CategoryBanners\Ui\Component\Form;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\Category as CategoryModel;

class CategoryOptions implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

 /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')
            ->addAttributeToFilter('level', 2) // Only top-level categories (level 2, as level 1 is root)
            ->addAttributeToFilter('is_active', 1)
            ->setOrder('position', 'ASC');

        $options = [];
        foreach ($collection as $category) {
            $options[] = [
                'value' => $category->getId(),
                'label' => $category->getName()
            ];
        }

        return $options;
    }

}