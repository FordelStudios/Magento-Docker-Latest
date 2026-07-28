<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Test\Unit\Service;

use Formula\Shadowfax\Helper\Data as ShadowfaxHelper;
use Formula\Shadowfax\Model\Data\ServiceabilityResult;
use Formula\Shadowfax\Model\Data\ShipmentResult;
use Formula\Shadowfax\Model\Data\TrackingResult;
use Formula\Shadowfax\Model\Request\ManifestPayloadBuilder;
use Formula\Shadowfax\Service\ShadowfaxApiService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ShadowFax API response PARSING only.
 *
 * We deliberately do NOT test network behaviour here — there are no ShadowFax
 * credentials/docs available, so any "it calls the real API and it works" claim
 * would be fabricated. These tests only prove: given a response shape matching our
 * ASSUMED schema, the service maps it into the correct typed Data\* result object.
 */
class ShadowfaxApiServiceTest extends TestCase
{
    /**
     * @var MockObject|ShadowfaxHelper
     */
    private $shadowfaxHelper;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|ManifestPayloadBuilder
     */
    private $manifestPayloadBuilder;

    private ShadowfaxApiService $service;

    protected function setUp(): void
    {
        $this->shadowfaxHelper = $this->getMockBuilder(ShadowfaxHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->manifestPayloadBuilder = $this->getMockBuilder(ManifestPayloadBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new ShadowfaxApiService(
            $this->shadowfaxHelper,
            new \Magento\Framework\HTTP\Client\Curl(),
            $this->logger,
            $this->manifestPayloadBuilder
        );
    }

    public function testParseShipmentResponseMapsAssumedFields(): void
    {
        $response = [
            'awb' => 'SF123456789',
            'shipment_id' => 'SHP-001',
            'courier_name' => 'ShadowFax Express',
        ];

        $result = $this->service->parseShipmentResponse($response);

        $this->assertInstanceOf(ShipmentResult::class, $result);
        $this->assertSame('SF123456789', $result->getAwb());
        $this->assertSame('SHP-001', $result->getShipmentId());
        $this->assertSame('ShadowFax Express', $result->getCourierName());
        $this->assertSame($response, $result->getRaw());
    }

    public function testParseShipmentResponseHandlesMissingFields(): void
    {
        $result = $this->service->parseShipmentResponse([]);

        $this->assertNull($result->getAwb());
        $this->assertNull($result->getShipmentId());
        $this->assertNull($result->getCourierName());
    }

    public function testParseServiceabilityResponseMapsAssumedFields(): void
    {
        $response = [
            'serviceable' => true,
            'eta_days' => 3,
            'estimated_delivery_date' => '2026-08-01',
        ];

        $result = $this->service->parseServiceabilityResponse($response);

        $this->assertInstanceOf(ServiceabilityResult::class, $result);
        $this->assertTrue($result->isServiceable());
        $this->assertSame(3, $result->getEtaDays());
        $this->assertSame('2026-08-01', $result->getEstDate());
    }

    public function testParseServiceabilityResponseDefaultsToNotServiceable(): void
    {
        $result = $this->service->parseServiceabilityResponse([]);

        $this->assertFalse($result->isServiceable());
        $this->assertNull($result->getEtaDays());
        $this->assertNull($result->getEstDate());
    }

    public function testParseTrackingResponseMapsAssumedFields(): void
    {
        $response = [
            'status_code' => 'OUT_FOR_DELIVERY',
            'status_label' => 'Out for delivery',
        ];

        $result = $this->service->parseTrackingResponse($response);

        $this->assertInstanceOf(TrackingResult::class, $result);
        $this->assertSame('OUT_FOR_DELIVERY', $result->getStatusCode());
        $this->assertSame('Out for delivery', $result->getStatusLabel());
    }
}
