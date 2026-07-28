<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Model\DeliveryPartner;

use Formula\DeliveryPartner\Api\Data\DeliveryShipmentResultInterface;
use Formula\DeliveryPartner\Api\DeliveryPartnerInterface;
use Formula\DeliveryPartner\Model\Data\DeliveryShipmentResult;
use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\Shadowfax\Model\Config\OrderStatus;
use Formula\Shadowfax\Service\ShadowfaxApiService;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Adapts the existing ShadowfaxApiService (ShipmentResult DTO-returning) to
 * the neutral Formula_DeliveryPartner contract.
 *
 * This class does NOT reimplement or alter any ShadowFax shipment-creation
 * logic — it only wraps ShadowfaxApiService::createShipment()'s existing
 * ShipmentResult DTO into a DeliveryShipmentResultInterface.
 */
class ShadowfaxDeliveryPartner implements DeliveryPartnerInterface
{
    /**
     * @var ShadowfaxApiService
     */
    private ShadowfaxApiService $apiService;

    /**
     * @param ShadowfaxApiService $apiService
     */
    public function __construct(ShadowfaxApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return PartnerCode::SHADOWFAX;
    }

    /**
     * @param OrderInterface $order
     * @return DeliveryShipmentResultInterface
     */
    public function createShipment(OrderInterface $order): DeliveryShipmentResultInterface
    {
        $result = $this->apiService->createShipment($order);

        $awb = $result->getAwb();
        $successful = $awb !== null && $awb !== '';

        if ($successful) {
            // Set the ShadowFax "shipment created" status, mirroring how the Shiprocket path
            // sets 'shipment_created'. Status knowledge stays INSIDE the Shadowfax module here
            // (OrderStatus is Shadowfax-owned), so the neutral call sites remain status-agnostic.
            // We only set the status on the in-memory order; the call site persists it via its
            // own save (the same save that stores the shadowfax_* columns).
            $order->setStatus(OrderStatus::SHIPMENT_CREATED);
        }

        return new DeliveryShipmentResult(
            PartnerCode::SHADOWFAX,
            $result->getShipmentId(),
            $awb,
            $result->getCourierName(),
            $successful
        );
    }
}
