<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Test\Unit\Service;

use Formula\Shadowfax\Helper\Data as ShadowfaxHelper;
use Formula\Shadowfax\Model\Data\ServiceabilityResult;
use Formula\Shadowfax\Model\Data\ShipmentResult;
use Formula\Shadowfax\Model\Data\TrackingResult;
use Formula\Shadowfax\Model\Request\ManifestPayloadBuilder;
use Formula\Shadowfax\Service\ShadowfaxApiService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ShadowfaxApiService.
 *
 * Two surfaces are covered WITHOUT a live ShadowFax API (we have no credentials/docs):
 *  1. Response PARSING — given a body matching our ASSUMED schema, the service maps it
 *     into the correct typed Data\* result object.
 *  2. The request() entry point — by mocking Magento's Curl client we verify guards
 *     (integration disabled / unconfigured), non-2xx error handling, and one 2xx happy
 *     path end-to-end. This is NOT a claim that the real ShadowFax endpoints work — only
 *     that our HTTP wrapper behaves correctly given a mocked transport.
 */
class ShadowfaxApiServiceTest extends TestCase
{
    /**
     * @var MockObject|ShadowfaxHelper
     */
    private $shadowfaxHelper;

    /**
     * @var MockObject|Curl
     */
    private $curl;

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

        $this->curl = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->manifestPayloadBuilder = $this->getMockBuilder(ManifestPayloadBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new ShadowfaxApiService(
            $this->shadowfaxHelper,
            $this->curl,
            $this->logger,
            $this->manifestPayloadBuilder
        );
    }

    /**
     * Configure the helper mock as a fully-enabled, configured integration.
     */
    private function configureEnabledHelper(): void
    {
        $this->shadowfaxHelper->method('isEnabled')->willReturn(true);
        $this->shadowfaxHelper->method('getApiBaseUrl')->willReturn('https://api.shadowfax.example');
        $this->shadowfaxHelper->method('getApiToken')->willReturn('test-token');
        $this->shadowfaxHelper->method('isDebugMode')->willReturn(false);
    }

    // --- request() entry-point tests (mocked Curl) -------------------------------

    public function testRequestThrowsWhenIntegrationDisabled(): void
    {
        $this->shadowfaxHelper->method('isEnabled')->willReturn(false);
        $this->curl->expects($this->never())->method('get');
        $this->curl->expects($this->never())->method('post');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ShadowFax integration is not enabled.');

        $this->service->trackShipment('SF123');
    }

    public function testRequestThrowsWhenBaseUrlMissing(): void
    {
        $this->shadowfaxHelper->method('isEnabled')->willReturn(true);
        $this->shadowfaxHelper->method('getApiBaseUrl')->willReturn(null);
        $this->shadowfaxHelper->method('getApiToken')->willReturn('test-token');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ShadowFax API is not configured.');

        $this->service->trackShipment('SF123');
    }

    public function testRequestThrowsWhenTokenMissing(): void
    {
        $this->shadowfaxHelper->method('isEnabled')->willReturn(true);
        $this->shadowfaxHelper->method('getApiBaseUrl')->willReturn('https://api.shadowfax.example');
        $this->shadowfaxHelper->method('getApiToken')->willReturn(null);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ShadowFax API is not configured.');

        $this->service->trackShipment('SF123');
    }

    public function testRequestThrowsLocalizedExceptionOnNonSuccessStatus(): void
    {
        $this->configureEnabledHelper();
        $this->curl->method('getStatus')->willReturn(500);
        $this->curl->method('getBody')->willReturn('{"message":"boom"}');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ShadowFax API error (HTTP 500): boom');

        $this->service->trackShipment('SF123');
    }

    public function testTrackShipmentHappyPathReturnsParsedDto(): void
    {
        $this->configureEnabledHelper();
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn(
            '{"status_code":"IN_TRANSIT","status_label":"In transit"}'
        );
        $this->curl->expects($this->once())
            ->method('get')
            ->with($this->stringContains('https://api.shadowfax.example/api/v1/shipments/SF123/track'));

        $result = $this->service->trackShipment('SF123');

        $this->assertInstanceOf(TrackingResult::class, $result);
        $this->assertSame('IN_TRANSIT', $result->getStatusCode());
        $this->assertSame('In transit', $result->getStatusLabel());
    }

    // --- cancelShipment contract tests -------------------------------------------

    public function testCancelShipmentReturnsTrueOnSuccessfulCallWithoutSuccessKey(): void
    {
        $this->configureEnabledHelper();
        $this->curl->method('getStatus')->willReturn(200);
        // 2xx body with no explicit `success` key must be treated as success.
        $this->curl->method('getBody')->willReturn('{"message":"cancelled"}');

        $this->assertTrue($this->service->cancelShipment('SF123'));
    }

    public function testCancelShipmentReturnsFalseWhenBodyExplicitlyFalse(): void
    {
        $this->configureEnabledHelper();
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"success":false,"message":"already delivered"}');

        $this->assertFalse($this->service->cancelShipment('SF123'));
    }

    // --- pure response-parsing tests ---------------------------------------------

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
