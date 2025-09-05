<?php
namespace Formula\CategoryBentoBanners\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\App\ObjectManager;

class CategoryBentoBanner extends AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('formula_category_bento_banners', 'entity_id');
        
        $logger = ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
        $logger->info('Resource _construct: Initialized with table formula_category_bento_banners');
    }

    /**
     * Save object data
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    public function save(\Magento\Framework\Model\AbstractModel $object)
    {
        $logger = ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
        $logger->info('Resource Save: Starting save', ['object_data' => $object->getData()]);
        
        // Check if table exists
        $connection = $this->getConnection();
        $tableName = $this->getMainTable();
        $logger->info('Resource Save: Table name: ' . $tableName);
        
        try {
            $tableExists = $connection->isTableExists($tableName);
            $logger->info('Resource Save: Table exists check', ['exists' => $tableExists]);
            
            if (!$tableExists) {
                $logger->error('Resource Save: Table does not exist!');
                throw new \Exception('Table ' . $tableName . ' does not exist');
            }
            
            $logger->info('Resource Save: Object data before parent::save', ['data' => $object->getData()]);
            $result = parent::save($object);
            $logger->info('Resource Save: Successfully saved', ['object_id' => $object->getId(), 'data_after_save' => $object->getData()]);
            
            // Check if the record was actually inserted by counting records
            $select = $connection->select()->from($tableName, 'COUNT(*)');
            $count = $connection->fetchOne($select);
            $logger->info('Resource Save: Total records in table after save', ['count' => $count]);
            
            // Try to fetch the saved record
            if ($object->getId()) {
                $select = $connection->select()
                    ->from($tableName)
                    ->where('entity_id = ?', $object->getId());
                $savedRecord = $connection->fetchRow($select);
                $logger->info('Resource Save: Saved record data', ['record' => $savedRecord]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $logger->error('Resource Save: Exception during save', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
}