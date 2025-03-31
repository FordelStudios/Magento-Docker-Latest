<?php
namespace Formula\Blog\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        // Create blog_details table
        $tableName = $installer->getTable('blog_details');
        $table = $installer->getConnection()
            ->newTable($tableName)
            ->addColumn(
                'blog_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Blog ID'
            )
            ->addColumn(
                'title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Blog Title'
            )
            ->addColumn(
                'content',
                Table::TYPE_TEXT,
                '2M',
                ['nullable' => false],
                'Blog Content'
            )
            ->addColumn(
                'image',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Blog Image'
            )
            ->addColumn(
                'author',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Author Name'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Blog Creation Time'
            )
            ->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Blog Update Time'
            )
            ->addColumn(
                'is_published',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Is Blog Published'
            )
            ->addColumn(
                'meta_title',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Meta Title'
            )
            ->addColumn(
                'product_ids',
                Table::TYPE_TEXT,
                '64k',
                ['nullable' => true],
                'Related Product IDs'
            )
            ->setComment('Blog Details Table');
        
        $installer->getConnection()->createTable($table);
        
        $installer->endSetup();
    }
}