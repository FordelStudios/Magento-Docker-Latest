<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Cron;

use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\DeliveryPartner\Model\ShipmentResultPersister;
use Formula\Shadowfax\Helper\Data as ShadowfaxHelper;
use Formula\Shadowfax\Model\DeliveryPartner\ShadowfaxDeliveryPartner;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Self-healing backfill for orders routed to ShadowFax whose forward shipment was never
 * created (the place_after observer / Razorpay flow failed, or the AWB never came back).
 *
 * Mirrors the hardened Formula_Shiprocket BackfillShiprocketShipments cron, but scoped to
 * ShadowFax: it retries orders where delivery_partner = 'shadowfax' AND shadowfax_awb IS NULL.
 *
 * ⚠ P1-GATED — UNVERIFIED LIVE BEHAVIOUR. This cron calls the ShadowFax API (via the
 * ShadowfaxDeliveryPartner adapter → ShadowfaxApiService), whose endpoints/auth/response
 * shapes are ASSUMPTIONS until real ShadowFax merchant credentials land (see
 * ShadowfaxApiService's class docblock). The branching/persistence logic here is unit-safe,
 * but the actual retry against a live ShadowFax endpoint cannot be verified yet. Kept thin.
 *
 * DISCOVERY LIMITATION (reconcile at P1): this finds only orders already stamped
 * delivery_partner = 'shadowfax'. An order that failed BEFORE that stamp was written is not
 * auto-discovered here — the stamp is currently written only on a successful create (via the
 * ShipmentResultPersister). Whether we should stamp delivery-partner INTENT at attempt time
 * (so pure failures are retryable) is an open product decision flagged for confirmation.
 */
class BackfillShadowfaxShipments
{
    /**
     * Order states that mean the order is dead — never create a shipment for these.
     */
    private const EXCLUDED_STATES = ['canceled', 'closed'];

    /**
     * How far back to look for stuck orders (days).
     */
    private const LOOKBACK_DAYS = 30;

    /**
     * Max orders processed per cron run — keeps ShadowFax API pressure bounded.
     */
    private const BATCH_SIZE = 20;

    /**
     * @var ShadowfaxHelper
     */
    private $shadowfaxHelper;

    /**
     * @var ShadowfaxDeliveryPartner
     */
    private $shadowfaxDeliveryPartner;

    /**
     * @var ShipmentResultPersister
     */
    private $shipmentResultPersister;

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
     * @param ShadowfaxHelper $shadowfaxHelper
     * @param ShadowfaxDeliveryPartner $shadowfaxDeliveryPartner
     * @param ShipmentResultPersister $shipmentResultPersister
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ShadowfaxHelper $shadowfaxHelper,
        ShadowfaxDeliveryPartner $shadowfaxDeliveryPartner,
        ShipmentResultPersister $shipmentResultPersister,
        OrderRepositoryInterface $orderRepository,
        OrderCollectionFactory $orderCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->shadowfaxHelper = $shadowfaxHelper;
        $this->shadowfaxDeliveryPartner = $shadowfaxDeliveryPartner;
        $this->shipmentResultPersister = $shipmentResultPersister;
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
            if (!$this->shadowfaxHelper->isEnabled()) {
                return;
            }

            $orderIds = $this->findCandidateOrderIds();

            if (empty($orderIds)) {
                return;
            }

            $this->logger->info(
                'ShadowfaxBackfill: found ' . count($orderIds) . ' candidate ShadowFax order(s) to sync'
            );

            foreach ($orderIds as $orderId) {
                $this->processOrder((int) $orderId);
            }
        } catch (\Throwable $e) {
            $this->logger->error('ShadowfaxBackfill: cron aborted unexpectedly - ' . $e->getMessage());
        }
    }

    /**
     * Non-virtual ShadowFax-routed orders in a live state, within the lookback window, that
     * still have no ShadowFax AWB. Oldest first, bounded batch.
     *
     * @return int[]
     */
    private function findCandidateOrderIds(): array
    {
        $collection = $this->orderCollectionFactory->create();

        $lookbackFrom = (new \DateTime('-' . self::LOOKBACK_DAYS . ' days', new \DateTimeZone('UTC')))
            ->format('Y-m-d H:i:s');

        $collection->addFieldToFilter('delivery_partner', PartnerCode::SHADOWFAX)
            ->addFieldToFilter('shadowfax_awb', ['null' => true])
            ->addFieldToFilter('is_virtual', ['neq' => 1])
            ->addFieldToFilter('state', ['nin' => self::EXCLUDED_STATES])
            ->addFieldToFilter('created_at', ['gteq' => $lookbackFrom]);

        // Oldest first so the backlog drains in order; bound the batch via page size.
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
     * Create the ShadowFax shipment for a single order and persist the result.
     * Fully isolated: any failure is logged at ERROR and swallowed so the batch continues.
     *
     * @param int $orderId
     * @return void
     */
    private function processOrder(int $orderId): void
    {
        $incrementId = (string) $orderId;

        try {
            $order = $this->orderRepository->get($orderId);
            $incrementId = $order->getIncrementId();

            // Idempotency guard: re-check right before creating to avoid racing the
            // place_after observer / Razorpay flow that may have just synced it.
            if ($order->getData('shadowfax_awb')) {
                return;
            }

            $result = $this->shadowfaxDeliveryPartner->createShipment($order);

            if (!$result->isSuccessful()) {
                $this->logger->error(
                    'ShadowfaxBackfill: shipment creation returned unsuccessful for order ' . $incrementId
                );
                return;
            }

            // Stage delivery_partner + shadowfax_* columns onto the order.
            $this->shipmentResultPersister->apply($order, $result);

            $comment = sprintf(
                'ShadowFax shipment created for order. Shipment ID: %s, AWB: %s, Courier: %s',
                $result->getShipmentId() ?: 'Pending',
                $result->getAwb() ?: 'Pending',
                $result->getCourierName() ?: 'Pending'
            );
            $order->addStatusHistoryComment($comment);

            $this->orderRepository->save($order);

            $this->logger->info(
                'ShadowfaxBackfill: shipment created for order ' . $incrementId
                . ' (shadowfax_awb=' . ($result->getAwb() ?: 'Pending') . ')'
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                'ShadowfaxBackfill: failed to sync order ' . $incrementId . ' - ' . $e->getMessage()
            );
        }
    }
}
