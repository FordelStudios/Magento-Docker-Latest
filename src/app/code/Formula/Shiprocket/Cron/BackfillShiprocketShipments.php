<?php
declare(strict_types=1);

namespace Formula\Shiprocket\Cron;

use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Formula\Shiprocket\Helper\Data as ShiprocketHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Self-healing backfill for COD / wallet orders whose Shiprocket forward shipment
 * was never created (the sales_order_place_after observer did not fire, or the
 * shiprocket_order_id/shipment_id columns were missing so the IDs were dropped).
 *
 * Prepaid (razorpay) orders are handled by Formula_RazorpayApi after payment capture
 * and are intentionally NOT touched here.
 */
class BackfillShiprocketShipments
{
    /**
     * Payment methods eligible for backfill. Razorpay is deliberately excluded — it syncs
     * to Shiprocket via Formula_RazorpayApi after payment capture.
     */
    private const ELIGIBLE_PAYMENT_METHODS = ['cashondelivery', 'walletpayment'];

    /**
     * Order states that mean the order is dead — never create a shipment for these.
     */
    private const EXCLUDED_STATES = ['canceled', 'closed'];

    /**
     * How far back to look for stuck orders (days). Guards against re-processing ancient orders.
     */
    private const LOOKBACK_DAYS = 30;

    /**
     * Max orders processed per cron run — keeps Shiprocket API pressure bounded.
     */
    private const BATCH_SIZE = 20;

    /**
     * @var ShiprocketShipmentService
     */
    private $shiprocketShipmentService;

    /**
     * @var ShiprocketHelper
     */
    private $shiprocketHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ShiprocketShipmentService $shiprocketShipmentService
     * @param ShiprocketHelper $shiprocketHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ShiprocketShipmentService $shiprocketShipmentService,
        ShiprocketHelper $shiprocketHelper,
        OrderRepositoryInterface $orderRepository,
        OrderCollectionFactory $orderCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->shiprocketShipmentService = $shiprocketShipmentService;
        $this->shiprocketHelper = $shiprocketHelper;
        $this->orderRepository = $orderRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Execute cron job. Never throws — every order is isolated so one failure cannot abort the batch.
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            if (!$this->shiprocketHelper->isEnabled()) {
                return;
            }

            $orderIds = $this->findCandidateOrderIds();

            if (empty($orderIds)) {
                return;
            }

            $this->logger->info(
                'ShiprocketBackfill: found ' . count($orderIds) . ' candidate COD/wallet order(s) to sync'
            );

            foreach ($orderIds as $orderId) {
                $this->processOrder((int) $orderId);
            }
        } catch (\Throwable $e) {
            // The cron itself must never blow up (e.g. bad DB state). Log and move on.
            $this->logger->error('ShiprocketBackfill: cron aborted unexpectedly - ' . $e->getMessage());
        }
    }

    /**
     * Query sales_order for non-virtual COD/wallet orders in a live state, within the lookback
     * window, that still have no Shiprocket forward-shipment identifiers. Oldest first so the
     * backlog drains in order. Returns a bounded batch of entity ids.
     *
     * @return int[]
     */
    private function findCandidateOrderIds(): array
    {
        $collection = $this->orderCollectionFactory->create();

        // Restrict to COD / wallet by joining the payment table (method lives on sales_order_payment).
        $collection->getSelect()->join(
            ['sop' => $collection->getTable('sales_order_payment')],
            'main_table.entity_id = sop.parent_id',
            []
        )->where('sop.method IN (?)', self::ELIGIBLE_PAYMENT_METHODS);

        $lookbackFrom = (new \DateTime('-' . self::LOOKBACK_DAYS . ' days', new \DateTimeZone('UTC')))
            ->format('Y-m-d H:i:s');

        $collection->addFieldToFilter('is_virtual', ['neq' => 1])
            ->addFieldToFilter('state', ['nin' => self::EXCLUDED_STATES])
            ->addFieldToFilter('created_at', ['gteq' => $lookbackFrom])
            ->addFieldToFilter('shiprocket_order_id', ['null' => true])
            ->addFieldToFilter('shiprocket_shipment_id', ['null' => true]);

        // Guard against duplicate rows the payment join could produce.
        $collection->getSelect()->group('main_table.entity_id');
        // Oldest first so the backlog drains in order; bound the batch via LIMIT.
        // NB: use setPageSize/setOrder (respected on load) — getAllIds() would reset ORDER/LIMIT.
        $collection->setOrder('created_at', 'ASC');
        $collection->setPageSize(self::BATCH_SIZE);
        $collection->setCurPage(1);

        $orderIds = [];
        foreach ($collection as $order) {
            $orderIds[] = (int) $order->getId();
        }

        return $orderIds;
    }

    /**
     * Create the Shiprocket shipment for a single order and persist the result.
     * Fully isolated: any failure is logged at ERROR and swallowed so the batch continues.
     *
     * @param int $orderId
     * @return void
     */
    private function processOrder(int $orderId): void
    {
        $incrementId = (string) $orderId;

        try {
            // Reload fresh + fully-hydrated from the repository (collection rows are partial).
            $order = $this->orderRepository->get($orderId);
            $incrementId = $order->getIncrementId();

            // Idempotency guard: re-check now, right before creating, to avoid double-create
            // races with the sales_order_place_after observer that may have just synced it.
            if ($order->getData('shiprocket_order_id') || $order->getData('shiprocket_shipment_id')) {
                return;
            }

            $shipmentResult = $this->shiprocketShipmentService->createShipment($order);

            if (empty($shipmentResult['success'])) {
                $this->logger->error(
                    'ShiprocketBackfill: shipment creation returned unsuccessful for order ' . $incrementId
                );
                return;
            }

            // Persist identifiers — mirrors CodOrderShiprocketSync success path exactly.
            $order->setData('shiprocket_order_id', $shipmentResult['shiprocket_order_id']);
            $order->setData('shiprocket_shipment_id', $shipmentResult['shipment_id']);
            $order->setData('shiprocket_awb_number', $shipmentResult['awb_code']);
            $order->setData('shiprocket_courier_name', $shipmentResult['courier_name']);

            $comment = sprintf(
                'Shiprocket shipment created for order. Shipment ID: %s, AWB: %s, Courier: %s',
                $shipmentResult['shipment_id'],
                $shipmentResult['awb_code'] ?: 'Pending',
                $shipmentResult['courier_name'] ?: 'Pending'
            );
            $order->addStatusHistoryComment($comment);

            $this->orderRepository->save($order);

            $this->logger->info(
                'ShiprocketBackfill: shipment created for order ' . $incrementId
                . ' (shiprocket_order_id=' . $shipmentResult['shiprocket_order_id'] . ')'
            );
        } catch (\Throwable $e) {
            // One failure must not abort the batch — log visibly and continue to the next order.
            $this->logger->error(
                'ShiprocketBackfill: failed to sync order ' . $incrementId . ' - ' . $e->getMessage()
            );
        }
    }
}
