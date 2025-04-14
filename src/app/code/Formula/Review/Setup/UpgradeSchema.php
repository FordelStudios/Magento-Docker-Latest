<?php
namespace Formula\Review\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrades DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // Check if we're upgrading from an earlier version
        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            // Add is_recommended column to review table
            $this->addIsRecommendedColumn($setup);
            
            // Create review_images table
            $this->createReviewImagesTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * Add is_recommended column to review table
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function addIsRecommendedColumn(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('review');
        
        // Check if the column already exists
        if (!$connection->tableColumnExists($tableName, 'is_recommended')) {
            $connection->addColumn(
                $tableName,
                'is_recommended',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'nullable' => true,
                    'default' => 0,
                    'comment' => 'Is Product Recommended'
                ]
            );
        }
    }

    /**
     * Create review_images table
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    private function createReviewImagesTable(SchemaSetupInterface $setup)
    {
        $connection = $setup->getConnection();
        $tableName = $setup->getTable('review_images');
        
        // Only create the table if it doesn't exist
        if (!$connection->isTableExists($tableName)) {
            $table = $connection->newTable($tableName)
                ->addColumn(
                    'image_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Image ID'
                )
                ->addColumn(
                    'review_id',
                    Table::TYPE_BIGINT,
                    null,
                    [
                        'unsigned' => true,
                        'nullable' => false
                    ],
                    'Review ID'
                )
                ->addColumn(
                    'image_path',
                    Table::TYPE_TEXT,
                    255,
                    [
                        'nullable' => false
                    ],
                    'Image Path'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [
                        'nullable' => false,
                        'default' => Table::TIMESTAMP_INIT
                    ],
                    'Created At'
                )
                ->addIndex(
                    $setup->getIdxName('review_images', ['review_id']),
                    ['review_id']
                )
                ->addForeignKey(
                    $setup->getFkName('review_images', 'review_id', 'review', 'review_id'),
                    'review_id',
                    $setup->getTable('review'),
                    'review_id',
                    Table::ACTION_CASCADE
                )
                ->setComment('Review Images');
            
            $connection->createTable($table);
        }
    }
}