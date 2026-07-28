<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Setup\Patch\Data;

use Formula\Shadowfax\Model\Config\OrderStatus;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class AddShadowfaxOrderStatuses implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $statusTable = $this->moduleDataSetup->getTable('sales_order_status');
        $statusStateTable = $this->moduleDataSetup->getTable('sales_order_status_state');

        foreach (OrderStatus::getStatusMap() as $status => $statusData) {
            // Check if status already exists
            $select = $connection->select()
                ->from($statusTable)
                ->where('status = ?', $status);

            $existingStatus = $connection->fetchOne($select);

            if (!$existingStatus) {
                // Insert status
                $connection->insert(
                    $statusTable,
                    [
                        'status' => $status,
                        'label' => $statusData['label']
                    ]
                );
            }

            // Check if status-state mapping already exists
            $select = $connection->select()
                ->from($statusStateTable)
                ->where('status = ?', $status)
                ->where('state = ?', $statusData['state']);

            $existingMapping = $connection->fetchOne($select);

            if (!$existingMapping) {
                // Insert status-state mapping
                $connection->insert(
                    $statusStateTable,
                    [
                        'status' => $status,
                        'state' => $statusData['state'],
                        'is_default' => 0,
                        'visible_on_front' => 1
                    ]
                );
            }
        }

        $this->moduleDataSetup->endSetup();

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
