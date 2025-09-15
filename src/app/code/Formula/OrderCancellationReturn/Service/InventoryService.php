<?php
namespace Formula\OrderCancellationReturn\Service;

use Magento\CatalogInventory\Api\StockManagementInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager as ModuleManager;
use Psr\Log\LoggerInterface;

class InventoryService
{
    protected $stockManagement;
    protected $stockRegistry;
    protected $sourceItemRepository;
    protected $sourceItemsSave;
    protected $sourceItemFactory;
    protected $stockResolver;
    protected $getProductSalableQty;
    protected $placeReservationsForSalesEvent;
    protected $salesEventFactory;
    protected $salesChannelFactory;
    protected $itemToSellFactory;
    protected $searchCriteriaBuilder;
    protected $moduleManager;
    protected $logger;

    public function __construct(
        StockManagementInterface $stockManagement,
        StockRegistryInterface $stockRegistry,
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        StockResolverInterface $stockResolver,
        GetProductSalableQtyInterface $getProductSalableQty,
        PlaceReservationsForSalesEventInterface $placeReservationsForSalesEvent,
        SalesEventInterfaceFactory $salesEventFactory,
        SalesChannelInterfaceFactory $salesChannelFactory,
        ItemToSellInterfaceFactory $itemToSellFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ModuleManager $moduleManager,
        LoggerInterface $logger
    ) {
        $this->stockManagement = $stockManagement;
        $this->stockRegistry = $stockRegistry;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->stockResolver = $stockResolver;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->placeReservationsForSalesEvent = $placeReservationsForSalesEvent;
        $this->salesEventFactory = $salesEventFactory;
        $this->salesChannelFactory = $salesChannelFactory;
        $this->itemToSellFactory = $itemToSellFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->moduleManager = $moduleManager;
        $this->logger = $logger;
    }

    /**
     * Restore inventory for cancelled order items
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function restoreInventoryForCancellation($order)
    {
        $restoredItems = [];

        try {
            $this->logger->info('Starting inventory restoration for order: ' . $order->getIncrementId());

            foreach ($order->getAllVisibleItems() as $item) {
                $sku = $item->getSku();
                $qtyToRestore = $item->getQtyOrdered();

                $this->logger->info('Processing item: ' . $sku . ', Qty to restore: ' . $qtyToRestore);

                if ($qtyToRestore <= 0) {
                    continue;
                }

                $restored = $this->restoreProductInventory($sku, $qtyToRestore, $order->getStoreId());

                if ($restored) {
                    $restoredItems[] = [
                        'sku' => $sku,
                        'name' => $item->getName(),
                        'qty_restored' => $qtyToRestore
                    ];
                    $this->logger->info('Successfully restored inventory for SKU: ' . $sku);
                } else {
                    $this->logger->warning('Failed to restore inventory for SKU: ' . $sku);
                }
            }

            $this->logger->info('Inventory restoration completed. Items restored: ' . count($restoredItems));
            return $restoredItems;

        } catch (\Exception $e) {
            $this->logger->error('Inventory restoration failed for order ' . $order->getIncrementId() . ': ' . $e->getMessage());
            throw new LocalizedException(__('Failed to restore inventory: %1', $e->getMessage()));
        }
    }

    /**
     * Restore inventory for returned order items (only after physical receipt)
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function restoreInventoryForReturn($order)
    {
        return $this->restoreInventoryForCancellation($order);
    }

    /**
     * Restore product inventory using appropriate inventory system
     *
     * @param string $sku
     * @param float $qty
     * @param int $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function restoreProductInventory($sku, $qty, $storeId)
    {
        if ($this->isMSIEnabled()) {
            $this->logger->info('Using MSI (Multi-Source Inventory) for SKU: ' . $sku);
            return $this->restoreInventoryMSI($sku, $qty, $storeId);
        } else {
            $this->logger->info('Using Legacy inventory system for SKU: ' . $sku);
            return $this->restoreInventoryLegacy($sku, $qty);
        }
    }

    /**
     * Restore inventory using MSI (Multi-Source Inventory)
     *
     * @param string $sku
     * @param float $qty
     * @param int $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function restoreInventoryMSI($sku, $qty, $storeId)
    {
        try {
            // Create positive reservation to restore salable quantity
            $itemsToSell = [
                $this->itemToSellFactory->create([
                    'sku' => $sku,
                    'qty' => $qty
                ])
            ];

            // Create sales channel for website
            $salesChannel = $this->salesChannelFactory->create();
            $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
            $salesChannel->setCode('base');

            // Create a sales event for order cancellation
            $salesEvent = $this->salesEventFactory->create([
                'type' => SalesEventInterface::EVENT_ORDER_CANCELED,
                'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
                'objectId' => (string) time() // Use timestamp as unique identifier
            ]);

            // Place positive reservation (restores salable quantity)
            $this->placeReservationsForSalesEvent->execute(
                $itemsToSell,
                $salesChannel,
                $salesEvent
            );

            $this->logger->info('MSI salable quantity restored for SKU ' . $sku . ': ' . $qty . ' units via reservation');
            return true;

        } catch (\Exception $e) {
            $this->logger->error('MSI inventory restoration failed for SKU ' . $sku . ': ' . $e->getMessage());
            // Fallback to legacy method if MSI fails
            return $this->restoreInventoryLegacy($sku, $qty);
        }
    }

    /**
     * Restore inventory using legacy stock management
     *
     * @param string $sku
     * @param float $qty
     * @return bool
     */
    protected function restoreInventoryLegacy($sku, $qty)
    {
        try {
            $stockItem = $this->stockRegistry->getStockItemBySku($sku);

            if (!$stockItem->getId()) {
                throw new LocalizedException(__('Stock item not found for SKU: %1', $sku));
            }

            $oldQty = $stockItem->getQty();
            $newQty = $oldQty + $qty;

            $this->logger->info('Legacy inventory - SKU: ' . $sku . ', Old Qty: ' . $oldQty . ', New Qty: ' . $newQty);

            $stockItem->setQty($newQty);

            // Set back to in stock if quantity is restored
            if ($newQty > 0) {
                $stockItem->setIsInStock(true);
            }

            $this->stockRegistry->updateStockItemBySku($sku, $stockItem);

            $this->logger->info('Legacy inventory restoration completed for SKU: ' . $sku);
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Legacy inventory restoration failed for SKU ' . $sku . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if MSI (Multi-Source Inventory) is enabled
     *
     * @return bool
     */
    protected function isMSIEnabled()
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }

    /**
     * Create inventory restoration message for status history
     *
     * @param array $restoredItems
     * @return string
     */
    public function createInventoryRestorationMessage($restoredItems)
    {
        if (empty($restoredItems)) {
            return '';
        }

        $messages = [];
        foreach ($restoredItems as $item) {
            $messages[] = sprintf('%d units of %s', $item['qty_restored'], $item['name']);
        }
        
        return ' Inventory restored: ' . implode(', ', $messages);
    }
}