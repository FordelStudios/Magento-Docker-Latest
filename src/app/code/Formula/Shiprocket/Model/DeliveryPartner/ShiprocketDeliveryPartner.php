<?php
declare(strict_types=1);

namespace Formula\Shiprocket\Model\DeliveryPartner;

use Formula\DeliveryPartner\Api\Data\DeliveryShipmentResultInterface;
use Formula\DeliveryPartner\Api\DeliveryPartnerInterface;
use Formula\DeliveryPartner\Model\Data\DeliveryShipmentResult;
use Formula\DeliveryPartner\Model\PartnerCode;
use Formula\Shiprocket\Service\ShiprocketShipmentService;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Adapts the existing ShiprocketShipmentService (array-returning) to the
 * neutral Formula_DeliveryPartner contract.
 *
 * This class does NOT reimplement or alter any Shiprocket shipment-creation
 * logic — it only wraps ShiprocketShipmentService::createShipment()'s
 * existing array return shape into a DeliveryShipmentResultInterface.
 */
class ShiprocketDeliveryPartner implements DeliveryPartnerInterface
{
    /**
     * @var ShiprocketShipmentService
     */
    private ShiprocketShipmentService $shipmentService;

    /**
     * @param ShiprocketShipmentService $shipmentService
     */
    public function __construct(ShiprocketShipmentService $shipmentService)
    {
        $this->shipmentService = $shipmentService;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return PartnerCode::SHIPROCKET;
    }

    /**
     * @param OrderInterface $order
     * @return DeliveryShipmentResultInterface
     */
    public function createShipment(OrderInterface $order): DeliveryShipmentResultInterface
    {
        $result = $this->shipmentService->createShipment($order);

        $shipmentId = $result['shipment_id'] ?? null;
        $awb = $result['awb_code'] ?? null;
        $courierName = $result['courier_name'] ?? null;

        return new DeliveryShipmentResult(
            PartnerCode::SHIPROCKET,
            $shipmentId !== null ? (string) $shipmentId : null,
            $awb !== null ? (string) $awb : null,
            $courierName !== null ? (string) $courierName : null,
            $awb !== null && $awb !== ''
        );
    }
}
