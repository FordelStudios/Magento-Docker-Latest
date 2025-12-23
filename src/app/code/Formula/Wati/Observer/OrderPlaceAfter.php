<?php
declare(strict_types=1);

namespace Formula\Wati\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Formula\Wati\Service\WatiApiService;
use Formula\Wati\Helper\Data as WatiHelper;
use Psr\Log\LoggerInterface;

/**
 * Observer for sending WhatsApp notification when order is placed
 */
class OrderPlaceAfter implements ObserverInterface
{
    /**
     * @var WatiApiService
     */
    protected $watiApiService;

    /**
     * @var WatiHelper
     */
    protected $watiHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param WatiApiService $watiApiService
     * @param WatiHelper $watiHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        WatiApiService $watiApiService,
        WatiHelper $watiHelper,
        LoggerInterface $logger
    ) {
        $this->watiApiService = $watiApiService;
        $this->watiHelper = $watiHelper;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->logger->info('Wati Observer: OrderPlaceAfter triggered');

        $order = $observer->getEvent()->getOrder();

        if (!$order || !$order->getId()) {
            $this->logger->info('Wati Observer: No order or order ID');
            return;
        }

        $this->logger->info('Wati Observer: Processing order ' . $order->getIncrementId());

        if (!$this->watiHelper->isEnabled()) {
            $this->logger->info('Wati Observer: Wati is disabled');
            return;
        }

        // Skip virtual orders (no shipping address)
        if ($order->getIsVirtual()) {
            $this->logger->debug('Wati: Skipping virtual order ' . $order->getIncrementId());
            return;
        }

        try {
            $this->logger->info('Wati: Sending order placed notification', [
                'order_id' => $order->getIncrementId(),
                'status' => 'pending'
            ]);

            $result = $this->watiApiService->sendOrderStatusNotification($order, 'pending');

            if (!$result['success']) {
                $this->logger->warning('Wati: Order placed notification failed', [
                    'order_id' => $order->getIncrementId(),
                    'error' => $result['error'] ?? 'Unknown'
                ]);
            }

        } catch (\Exception $e) {
            // Log but don't fail the order - notifications should never break order flow
            $this->logger->error('Wati: Exception in order placed observer', [
                'order_id' => $order->getIncrementId(),
                'exception' => $e->getMessage()
            ]);
        }
    }
}
