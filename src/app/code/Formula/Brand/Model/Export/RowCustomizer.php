<?php
declare(strict_types=1);

namespace Formula\Brand\Model\Export;

use Formula\Brand\Model\ResourceModel\Brand\CollectionFactory as BrandCollectionFactory;
use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\Framework\App\ResourceConnection;

class RowCustomizer implements RowCustomizerInterface
{
    private const BRAND_NAME_COLUMN = 'brand_name';

    private array $brandData = [];

    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly BrandCollectionFactory $brandCollectionFactory
    ) {}

    public function prepareData($collection, $productIds)
    {
        if (empty($productIds)) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $entityIntTable = $this->resourceConnection->getTableName('catalog_product_entity_int');
        $eavAttributeTable = $this->resourceConnection->getTableName('eav_attribute');

        // Fetch brand_id for each product (global scope, store_id = 0)
        $select = $connection->select()
            ->from(['cpei' => $entityIntTable], ['entity_id', 'value'])
            ->join(['ea' => $eavAttributeTable], 'ea.attribute_id = cpei.attribute_id', [])
            ->where('ea.attribute_code = ?', 'brand')
            ->where('cpei.store_id = ?', 0)
            ->where('cpei.entity_id IN (?)', $productIds);

        $productBrandMap = $connection->fetchPairs($select); // [productId => brandId]

        if (empty($productBrandMap)) {
            return;
        }

        $brandIds = array_unique(array_values($productBrandMap));
        $brandCollection = $this->brandCollectionFactory->create();
        $brandCollection->addFieldToFilter('brand_id', ['in' => $brandIds]);

        $brandNames = [];
        foreach ($brandCollection as $brand) {
            $brandNames[(int)$brand->getId()] = $brand->getName();
        }

        foreach ($productBrandMap as $productId => $brandId) {
            $this->brandData[(int)$productId] = $brandNames[(int)$brandId] ?? '';
        }
    }

    public function addHeaderColumns($columns)
    {
        $columns[] = self::BRAND_NAME_COLUMN;
        return $columns;
    }

    public function addData($dataRow, $productId)
    {
        $dataRow[self::BRAND_NAME_COLUMN] = $this->brandData[(int)$productId] ?? '';
        return $dataRow;
    }

    public function getAdditionalRowsCount($additionalRowsCount, $productId)
    {
        return $additionalRowsCount;
    }
}
