<?php
namespace Formula\Shiprocket\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order;

class AddShiprocketOrderStatuses implements DataPatchInterface
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

        // Define custom statuses with their states
        $statuses = [
            // Processing state statuses (order is being fulfilled)
            [
                'status' => 'shipment_created',
                'label' => 'Shipment Created',
                'state' => Order::STATE_PROCESSING,
                'is_default' => 0,
                'visible_on_front' => 1
            ],
            [
                'status' => 'pickup_scheduled',
                'label' => 'Pickup Scheduled',
                'state' => Order::STATE_PROCESSING,
                'is_default' => 0,
                'visible_on_front' => 1
            ],
            [
                'status' => 'shipped',
                'label' => 'Shipped',
                'state' => Order::STATE_PROCESSING,
                'is_default' => 0,
                'visible_on_front' => 1
            ],
            [
                'status' => 'in_transit',
                'label' => 'In Transit',
                'state' => Order::STATE_PROCESSING,
                'is_default' => 0,
                'visible_on_front' => 1
            ],
            [
                'status' => 'out_for_delivery',
                'label' => 'Out for Delivery',
                'state' => Order::STATE_PROCESSING,
                'is_default' => 0,
                'visible_on_front' => 1
            ],
            // Complete state status
            [
                'status' => 'delivered',
                'label' => 'Delivered',
                'state' => Order::STATE_COMPLETE,
                'is_default' => 0,
                'visible_on_front' => 1
            ],
            // Canceled state status
            [
                'status' => 'shipment_cancelled',
                'label' => 'Shipment Cancelled',
                'state' => Order::STATE_CANCELED,
                'is_default' => 0,
                'visible_on_front' => 1
            ],
            // RTO statuses
            [
                'status' => 'rto_initiated',
                'label' => 'RTO Initiated',
                'state' => Order::STATE_PROCESSING,
                'is_default' => 0,
                'visible_on_front' => 1
            ],
            [
                'status' => 'rto_delivered',
                'label' => 'RTO Delivered',
                'state' => Order::STATE_CLOSED,
                'is_default' => 0,
                'visible_on_front' => 1
            ],
        ];

        $connection = $this->moduleDataSetup->getConnection();
        $statusTable = $this->moduleDataSetup->getTable('sales_order_status');
        $statusStateTable = $this->moduleDataSetup->getTable('sales_order_status_state');

        foreach ($statuses as $statusData) {
            // Check if status already exists
            $select = $connection->select()
                ->from($statusTable)
                ->where('status = ?', $statusData['status']);

            $existingStatus = $connection->fetchOne($select);

            if (!$existingStatus) {
                // Insert status
                $connection->insert(
                    $statusTable,
                    [
                        'status' => $statusData['status'],
                        'label' => $statusData['label']
                    ]
                );
            }

            // Check if status-state mapping already exists
            $select = $connection->select()
                ->from($statusStateTable)
                ->where('status = ?', $statusData['status'])
                ->where('state = ?', $statusData['state']);

            $existingMapping = $connection->fetchOne($select);

            if (!$existingMapping) {
                // Insert status-state mapping
                $connection->insert(
                    $statusStateTable,
                    [
                        'status' => $statusData['status'],
                        'state' => $statusData['state'],
                        'is_default' => $statusData['is_default'],
                        'visible_on_front' => $statusData['visible_on_front']
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
