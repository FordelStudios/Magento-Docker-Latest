<?php

declare(strict_types=1);

namespace Formula\OrderCancellationReturn\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order;

/**
 * Add return-related order statuses to Magento
 */
class AddReturnOrderStatuses implements DataPatchInterface
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
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $statuses = [
            // Return workflow statuses
            ['status' => 'return_requested', 'label' => 'Return Requested'],
            ['status' => 'return_approved', 'label' => 'Return Approved'],
            ['status' => 'return_rejected', 'label' => 'Return Rejected'],
            ['status' => 'return_pickup_scheduled', 'label' => 'Return Pickup Scheduled'],
            ['status' => 'return_in_transit', 'label' => 'Return In Transit'],
            ['status' => 'return_received', 'label' => 'Return Received'],
            ['status' => 'refund_initiated', 'label' => 'Refund Initiated'],
            ['status' => 'refund_completed', 'label' => 'Refund Completed'],
            ['status' => 'return_cancelled', 'label' => 'Return Cancelled'],
        ];

        // State mappings - all return statuses map to 'complete' state
        // so the order stays in completed state while going through return process
        $stateMapping = [
            'return_requested' => Order::STATE_COMPLETE,
            'return_approved' => Order::STATE_COMPLETE,
            'return_rejected' => Order::STATE_COMPLETE,
            'return_pickup_scheduled' => Order::STATE_COMPLETE,
            'return_in_transit' => Order::STATE_COMPLETE,
            'return_received' => Order::STATE_COMPLETE,
            'refund_initiated' => Order::STATE_COMPLETE,
            'refund_completed' => Order::STATE_CLOSED,
            'return_cancelled' => Order::STATE_COMPLETE,
        ];

        $connection = $this->moduleDataSetup->getConnection();
        $statusTable = $this->moduleDataSetup->getTable('sales_order_status');
        $statusStateTable = $this->moduleDataSetup->getTable('sales_order_status_state');

        foreach ($statuses as $statusData) {
            // Insert status (ignore if exists)
            $connection->insertOnDuplicate(
                $statusTable,
                $statusData,
                ['label']
            );

            // Insert status-state mapping
            $state = $stateMapping[$statusData['status']] ?? Order::STATE_COMPLETE;
            $connection->insertOnDuplicate(
                $statusStateTable,
                [
                    'status' => $statusData['status'],
                    'state' => $state,
                    'is_default' => 0,
                    'visible_on_front' => 1,
                ],
                ['state', 'is_default', 'visible_on_front']
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
