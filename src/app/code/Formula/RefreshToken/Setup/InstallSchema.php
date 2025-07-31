<?php
namespace Formula\RefreshToken\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('formula_refresh_token')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('formula_refresh_token')
            )
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Entity ID'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Customer ID'
                )
                ->addColumn(
                    'token',
                    Table::TYPE_TEXT,
                    64,
                    ['nullable' => false],
                    'Refresh Token'
                )
                ->addColumn(
                    'expires_at',
                    Table::TYPE_DATETIME,
                    null,
                    ['nullable' => false],
                    'Expiration Date'
                )
                ->addIndex(
                    $installer->getIdxName('formula_refresh_token', ['token']),
                    ['token'],
                    ['type' => 'unique']
                )
                ->setComment('Formula Refresh Token Table');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
