<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Test\Unit\Model;

use Formula\Shadowfax\Helper\Data as ShadowfaxHelper;
use Formula\Shadowfax\Model\ShadowfaxWebhook;
use Formula\Shadowfax\Model\ShadowfaxWebhookHandler;
use Magento\Framework\Webapi\Rest\Request;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the webhook entrypoint's secret verification and dispatch.
 *
 * These tests cover the LOGIC of "does the entrypoint trust this call and
 * delegate to the handler" against our ASSUMED header name
 * (ShadowfaxWebhook::HEADER_SECRET) and payload field names
 * (ShadowfaxWebhook::FIELD_*). They do not, and cannot, prove a real
 * ShadowFax callback will look like this — see class docblock.
 */
class ShadowfaxWebhookTest extends TestCase
{
    private const CONFIGURED_SECRET = 'whsec_test_shadowfax_secret';

    /**
     * @var MockObject|ShadowfaxWebhookHandler
     */
    private $webhookHandler;

    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|ShadowfaxHelper
     */
    private $shadowfaxHelper;

    private ShadowfaxWebhook $webhook;

    protected function setUp(): void
    {
        $this->webhookHandler = $this->getMockBuilder(ShadowfaxWebhookHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->shadowfaxHelper = $this->getMockBuilder(ShadowfaxHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shadowfaxHelper->method('isEnabled')->willReturn(true);
        $this->shadowfaxHelper->method('isDebugMode')->willReturn(false);
        $this->shadowfaxHelper->method('getWebhookSecret')->willReturn(self::CONFIGURED_SECRET);

        $this->webhook = new ShadowfaxWebhook(
            $this->webhookHandler,
            $this->request,
            $this->logger,
            $this->shadowfaxHelper
        );
    }

    public function testCorrectSecretDelegatesToHandler(): void
    {
        $this->request->method('getHeader')
            ->with(ShadowfaxWebhook::HEADER_SECRET)
            ->willReturn(self::CONFIGURED_SECRET);

        $payload = [
            'awb' => 'AWB123456',
            'shipment_id' => 'SF-SHIP-1',
            'status' => 'DELIVERED',
        ];

        $this->webhookHandler->expects($this->once())
            ->method('handleTrackingUpdate')
            ->with('AWB123456', 'SF-SHIP-1', 'DELIVERED')
            ->willReturn(['success' => true, 'message' => 'Order status updated to shadowfax_delivered']);

        $result = $this->webhook->handleStatusUpdate($payload);

        $this->assertTrue($result['success']);
        $this->assertSame('Order status updated to shadowfax_delivered', $result['message']);
    }

    public function testWrongSecretIsRejectedAndHandlerNotCalled(): void
    {
        $this->request->method('getHeader')
            ->with(ShadowfaxWebhook::HEADER_SECRET)
            ->willReturn('an-attacker-supplied-wrong-secret');

        $this->webhookHandler->expects($this->never())->method('handleTrackingUpdate');

        $result = $this->webhook->handleStatusUpdate(['awb' => 'AWB1', 'status' => 'DELIVERED']);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid webhook secret', $result['message']);
    }

    public function testMissingSecretHeaderIsRejectedAndHandlerNotCalled(): void
    {
        $this->request->method('getHeader')
            ->with(ShadowfaxWebhook::HEADER_SECRET)
            ->willReturn(false);

        $this->webhookHandler->expects($this->never())->method('handleTrackingUpdate');

        $result = $this->webhook->handleStatusUpdate(['awb' => 'AWB1', 'status' => 'DELIVERED']);

        $this->assertFalse($result['success']);
    }

    public function testUnconfiguredSecretRejectsEvenIfHeaderPresent(): void
    {
        $this->shadowfaxHelper = $this->getMockBuilder(ShadowfaxHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shadowfaxHelper->method('isEnabled')->willReturn(true);
        $this->shadowfaxHelper->method('isDebugMode')->willReturn(false);
        $this->shadowfaxHelper->method('getWebhookSecret')->willReturn(null);

        $webhook = new ShadowfaxWebhook(
            $this->webhookHandler,
            $this->request,
            $this->logger,
            $this->shadowfaxHelper
        );

        $this->request->method('getHeader')->willReturn('anything');

        $this->webhookHandler->expects($this->never())->method('handleTrackingUpdate');

        $result = $webhook->handleStatusUpdate(['awb' => 'AWB1', 'status' => 'DELIVERED']);

        $this->assertFalse($result['success']);
    }

    public function testDisabledModuleIsRejectedAndHandlerNotCalledEvenWithCorrectSecret(): void
    {
        $this->shadowfaxHelper = $this->getMockBuilder(ShadowfaxHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shadowfaxHelper->method('isEnabled')->willReturn(false);
        $this->shadowfaxHelper->method('getWebhookSecret')->willReturn(self::CONFIGURED_SECRET);

        $webhook = new ShadowfaxWebhook(
            $this->webhookHandler,
            $this->request,
            $this->logger,
            $this->shadowfaxHelper
        );

        // Secret verification must not even need to run when disabled, but
        // stub it anyway in case ordering changes.
        $this->request->method('getHeader')->willReturn(self::CONFIGURED_SECRET);

        $this->webhookHandler->expects($this->never())->method('handleTrackingUpdate');

        $result = $webhook->handleStatusUpdate(['awb' => 'AWB1', 'status' => 'DELIVERED']);

        $this->assertFalse($result['success']);
        $this->assertSame('ShadowFax integration is disabled', $result['message']);
    }

    public function testHandlerExceptionIsCaughtLoggedAndReturnedAsFailureNotThrown(): void
    {
        $this->request->method('getHeader')
            ->with(ShadowfaxWebhook::HEADER_SECRET)
            ->willReturn(self::CONFIGURED_SECRET);

        $this->webhookHandler->expects($this->once())
            ->method('handleTrackingUpdate')
            ->willThrowException(new \RuntimeException('order repository blew up'));

        $this->logger->expects($this->once())->method('error');

        $result = $this->webhook->handleStatusUpdate(['awb' => 'AWB1', 'status' => 'DELIVERED']);

        $this->assertFalse($result['success']);
        $this->assertSame('order repository blew up', $result['message']);
    }

    public function testMissingPayloadFieldsAreExtractedAsNullNotEmptyString(): void
    {
        $this->request->method('getHeader')
            ->with(ShadowfaxWebhook::HEADER_SECRET)
            ->willReturn(self::CONFIGURED_SECRET);

        $this->webhookHandler->expects($this->once())
            ->method('handleTrackingUpdate')
            ->with(null, null, 'DELIVERED')
            ->willReturn(['success' => true, 'message' => 'ok']);

        $this->webhook->handleStatusUpdate(['status' => 'DELIVERED']);
    }
}
