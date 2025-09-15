<?php
namespace Formula\Wallet\Setup\Patch\Schema;

use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\DB\Ddl\Table;

class AddWalletAmountToQuoteAndOrder implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(SchemaSetupInterface $schemaSetup)
    {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();

        // Add wallet_amount_used column to quote table
        $quoteTable = $this->schemaSetup->getTable('quote');
        if ($this->schemaSetup->getConnection()->isTableExists($quoteTable)) {
            $this->schemaSetup->getConnection()->addColumn(
                $quoteTable,
                'wallet_amount_used',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '20,4',
                    'nullable' => true,
                    'default' => '0.0000',
                    'comment' => 'Wallet Amount Used'
                ]
            );
        }

        // Add wallet_amount_used column to sales_order table
        $orderTable = $this->schemaSetup->getTable('sales_order');
        if ($this->schemaSetup->getConnection()->isTableExists($orderTable)) {
            $this->schemaSetup->getConnection()->addColumn(
                $orderTable,
                'wallet_amount_used',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '20,4',
                    'nullable' => true,
                    'default' => '0.0000',
                    'comment' => 'Wallet Amount Used'
                ]
            );
        }

        // Add base_wallet_amount_used column to quote table
        if ($this->schemaSetup->getConnection()->isTableExists($quoteTable)) {
            $this->schemaSetup->getConnection()->addColumn(
                $quoteTable,
                'base_wallet_amount_used',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '20,4',
                    'nullable' => true,
                    'default' => '0.0000',
                    'comment' => 'Base Wallet Amount Used'
                ]
            );
        }

        // Add base_wallet_amount_used column to sales_order table
        if ($this->schemaSetup->getConnection()->isTableExists($orderTable)) {
            $this->schemaSetup->getConnection()->addColumn(
                $orderTable,
                'base_wallet_amount_used',
                [
                    'type' => Table::TYPE_DECIMAL,
                    'length' => '20,4',
                    'nullable' => true,
                    'default' => '0.0000',
                    'comment' => 'Base Wallet Amount Used'
                ]
            );
        }

        $this->schemaSetup->endSetup();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}