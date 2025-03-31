<?php
namespace Formula\Blog\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $tableName = $setup->getTable('blog_details');

        // Rename creation_time to created_at
        $setup->getConnection()->changeColumn(
            $tableName,
            'creation_time',
            'created_at',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                'nullable' => false,
                'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT,
                'comment' => 'Blog Creation Time'
            ]
        );

        // Rename update_time to updated_at
        $setup->getConnection()->changeColumn(
            $tableName,
            'update_time',
            'updated_at',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                'nullable' => false,
                'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE,
                'comment' => 'Blog Update Time'
            ]
        );

        // Rename is_active to is_published
        $setup->getConnection()->changeColumn(
            $tableName,
            'is_active',
            'is_published',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'nullable' => false,
                'default' => 1,
                'comment' => 'Is Blog Published'
            ]
        );

        // Drop unwanted meta columns
        $setup->getConnection()->dropColumn($tableName, 'meta_title');
        $setup->getConnection()->dropColumn($tableName, 'meta_keywords');
        $setup->getConnection()->dropColumn($tableName, 'meta_description');
        $setup->getConnection()->dropColumn($tableName, 'url_key');

        $setup->endSetup();
    }
}