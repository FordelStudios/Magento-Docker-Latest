<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Model\DeliveryPartner;

use Formula\DeliveryPartner\Api\Data\DeliveryShipmentResultInterface;
use Formula\DeliveryPartner\Api\DeliveryPartnerInterface;
use Formula\DeliveryPartner\Model\Data\DeliveryShipmentResult;
use Formula\DeliveryPartner\Model\PartnerCode;
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

        return new DeliveryShipmentResult(
            PartnerCode::SHADOWFAX,
            $result->getShipmentId(),
            $awb,
            $result->getCourierName(),
            $awb !== null && $awb !== ''
        );
    }
}
