<?php
declare(strict_types=1);

namespace Formula\DeliveryPartner\Test\Unit\Model\Data;

use Formula\DeliveryPartner\Model\Data\DeliveryShipmentResult;
use Formula\DeliveryPartner\Model\PartnerCode;
use PHPUnit\Framework\TestCase;

class DeliveryShipmentResultTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $result = new DeliveryShipmentResult(
            PartnerCode::SHADOWFAX,
            'SHIP123',
            'AWB456',
            'Delhivery',
            true
        );

        $this->assertSame(PartnerCode::SHADOWFAX, $result->getPartnerCode());
        $this->assertSame('SHIP123', $result->getShipmentId());
        $this->assertSame('AWB456', $result->getAwb());
        $this->assertSame('Delhivery', $result->getCourierName());
        $this->assertTrue($result->isSuccessful());
    }

    public function testNullableFieldsCanBeNull(): void
    {
        $result = new DeliveryShipmentResult(
            PartnerCode::SHIPROCKET,
            null,
            null,
            null,
            false
        );

        $this->assertSame(PartnerCode::SHIPROCKET, $result->getPartnerCode());
        $this->assertNull($result->getShipmentId());
        $this->assertNull($result->getAwb());
        $this->assertNull($result->getCourierName());
        $this->assertFalse($result->isSuccessful());
    }

    public function testIsSuccessfulReflectsConstructorFlagIndependentlyOfOtherFields(): void
    {
        $successfulWithoutAwb = new DeliveryShipmentResult(PartnerCode::SHIPROCKET, 'S1', null, null, true);
        $failedWithAwb = new DeliveryShipmentResult(PartnerCode::SHIPROCKET, 'S2', 'AWB1', 'Courier', false);

        $this->assertTrue($successfulWithoutAwb->isSuccessful());
        $this->assertFalse($failedWithAwb->isSuccessful());
    }
}
