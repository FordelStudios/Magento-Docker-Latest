<?php
declare(strict_types=1);

namespace Formula\Shadowfax\Service;

use Formula\Shadowfax\Helper\Data as ShadowfaxHelper;
use Formula\Shadowfax\Model\Data\ServiceabilityResult;
use Formula\Shadowfax\Model\Data\ShipmentResult;
use Formula\Shadowfax\Model\Data\TrackingResult;
use Formula\Shadowfax\Model\Request\ManifestPayloadBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

/**
 * ShadowFax delivery-partner API client.
 *
 * IMPORTANT — UNVERIFIED AGAINST THE REAL SHADOWFAX API:
 * We do not have ShadowFax merchant credentials or their API documentation yet.
 * Endpoint paths, the auth header shape, and response field names below are all
 * ASSUMPTIONS based on typical Indian courier-aggregator conventions (mirroring the
 * house pattern in Formula_Shiprocket's ShiprocketShipmentService). None of this has
 * been exercised against a live ShadowFax endpoint. Only the response-PARSING logic
 * is unit-tested (see Test/Unit/Service/ShadowfaxApiServiceTest.php); the actual HTTP
 * call path cannot be verified without real credentials.
 *
 * Per Formula's research, ShadowFax auth is token/API-key based (unlike Shiprocket,
 * which re-authenticates via email/password on every request) — so this client sends
 * the decrypted token from Helper::getApiToken() as a bearer token on every call
 * rather than building a login flow.
 *
 * @todo Reconcile endpoint paths, auth header name, and response field names against
 *       real ShadowFax API docs when P1 credentials land.
 */
class ShadowfaxApiService
{
    /**
     * @todo ASSUMED path — reconcile against real ShadowFax API docs.
     */
    private const ENDPOINT_SERVICEABILITY = 'api/v1/serviceability';

    /**
     * @todo ASSUMED path — reconcile against real ShadowFax API docs.
     */
    private const ENDPOINT_CREATE_SHIPMENT = 'api/v1/shipments';

    /**
     * @todo ASSUMED path (sprintf placeholder for AWB) — reconcile against real docs.
     */
    private const ENDPOINT_CANCEL_SHIPMENT = 'api/v1/shipments/%s/cancel';

    /**
     * @todo ASSUMED path (sprintf placeholder for AWB) — reconcile against real docs.
     */
    private const ENDPOINT_TRACK_SHIPMENT = 'api/v1/shipments/%s/track';

    /**
     * @var ShadowfaxHelper
     */
    private $shadowfaxHelper;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ManifestPayloadBuilder
     */
    private $manifestPayloadBuilder;

    /**
     * @param ShadowfaxHelper $shadowfaxHelper
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param ManifestPayloadBuilder $manifestPayloadBuilder
     */
    public function __construct(
        ShadowfaxHelper $shadowfaxHelper,
        Curl $curl,
        LoggerInterface $logger,
        ManifestPayloadBuilder $manifestPayloadBuilder
    ) {
        $this->shadowfaxHelper = $shadowfaxHelper;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->manifestPayloadBuilder = $manifestPayloadBuilder;
    }

    /**
     * @param string $pincode
     * @param float $weight
     * @return ServiceabilityResult
     * @throws LocalizedException
     */
    public function checkServiceability(string $pincode, float $weight): ServiceabilityResult
    {
        $response = $this->request('GET', self::ENDPOINT_SERVICEABILITY, [
            'pickup_pincode' => $this->shadowfaxHelper->getPickupPostcode(),
            'drop_pincode' => $pincode,
            'weight' => $weight,
        ]);

        return $this->parseServiceabilityResponse($response);
    }

    /**
     * @param OrderInterface $order
     * @return ShipmentResult
     * @throws LocalizedException
     */
    public function createShipment(OrderInterface $order): ShipmentResult
    {
        $payload = $this->manifestPayloadBuilder->build($order);
        $response = $this->request('POST', self::ENDPOINT_CREATE_SHIPMENT, $payload);

        return $this->parseShipmentResponse($response);
    }

    /**
     * Cancel a shipment by AWB.
     *
     * request() already throws on any non-2xx status, so reaching this point means the
     * HTTP call succeeded — treat that as a successful cancellation (matching the
     * Formula_Shiprocket house pattern) unless the 2xx body EXPLICITLY says
     * success === false. A missing `success` key on a 2xx response is treated as
     * success, not failure.
     *
     * @todo The `success` response key is an ASSUMED field name — reconcile against
     *       real ShadowFax API docs when P1 credentials land.
     *
     * @param string $awb
     * @return bool
     * @throws LocalizedException
     */
    public function cancelShipment(string $awb): bool
    {
        $endpoint = sprintf(self::ENDPOINT_CANCEL_SHIPMENT, $awb);
        $response = $this->request('POST', $endpoint, ['awb' => $awb]);

        return ($response['success'] ?? true) !== false;
    }

    /**
     * @param string $awb
     * @return TrackingResult
     * @throws LocalizedException
     */
    public function trackShipment(string $awb): TrackingResult
    {
        $endpoint = sprintf(self::ENDPOINT_TRACK_SHIPMENT, $awb);
        $response = $this->request('GET', $endpoint, []);

        return $this->parseTrackingResponse($response);
    }

    /**
     * Pure mapping: assumed create-shipment response -> ShipmentResult.
     * Public (not just private) so it is directly unit-testable without a network call.
     *
     * @param array $response
     * @return ShipmentResult
     */
    public function parseShipmentResponse(array $response): ShipmentResult
    {
        return new ShipmentResult(
            $response['awb'] ?? null,
            $response['shipment_id'] ?? null,
            $response['courier_name'] ?? null,
            $response
        );
    }

    /**
     * Pure mapping: assumed serviceability response -> ServiceabilityResult.
     * Public (not just private) so it is directly unit-testable without a network call.
     *
     * @param array $response
     * @return ServiceabilityResult
     */
    public function parseServiceabilityResponse(array $response): ServiceabilityResult
    {
        return new ServiceabilityResult(
            (bool) ($response['serviceable'] ?? false),
            isset($response['eta_days']) ? (int) $response['eta_days'] : null,
            $response['estimated_delivery_date'] ?? null,
            $response
        );
    }

    /**
     * Pure mapping: assumed tracking response -> TrackingResult.
     * Public (not just private) so it is directly unit-testable without a network call.
     *
     * @param array $response
     * @return TrackingResult
     */
    public function parseTrackingResponse(array $response): TrackingResult
    {
        return new TrackingResult(
            $response['status_code'] ?? null,
            $response['status_label'] ?? null,
            $response
        );
    }

    /**
     * Thin curl wrapper mirroring Formula_Shiprocket's ShiprocketShipmentService
     * pattern: JSON body, bearer auth header, debug-gated logging, non-2xx throws.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @return array
     * @throws LocalizedException
     */
    private function request(string $method, string $endpoint, array $params): array
    {
        if (!$this->shadowfaxHelper->isEnabled()) {
            throw new LocalizedException(__('ShadowFax integration is not enabled.'));
        }

        $baseUrl = $this->shadowfaxHelper->getApiBaseUrl();
        $token = $this->shadowfaxHelper->getApiToken();

        if (!$baseUrl || !$token) {
            throw new LocalizedException(__('ShadowFax API is not configured.'));
        }

        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');

        $this->curl->addHeader('Content-Type', 'application/json');
        // @todo ASSUMED auth header shape (bearer token) — reconcile against real docs.
        $this->curl->addHeader('Authorization', 'Bearer ' . $token);

        if ($this->shadowfaxHelper->isDebugMode()) {
            $this->logger->info('ShadowFax API request', [
                'method' => $method,
                'url' => $url,
                'params' => $params,
            ]);
        }

        if ($method === 'GET') {
            $query = $params ? ('?' . http_build_query($params)) : '';
            $this->curl->get($url . $query);
        } else {
            $this->curl->post($url, (string) json_encode($params));
        }

        $body = (string) $this->curl->getBody();
        $status = (int) $this->curl->getStatus();

        if ($this->shadowfaxHelper->isDebugMode()) {
            $this->logger->info('ShadowFax API response', [
                'status' => $status,
                'body' => $body,
            ]);
        }

        $decoded = json_decode($body, true);
        $decoded = is_array($decoded) ? $decoded : [];

        if ($status < 200 || $status >= 300) {
            $this->logger->error('ShadowFax API error', [
                'status' => $status,
                'body' => $body,
            ]);
            throw new LocalizedException(__(
                'ShadowFax API error (HTTP %1): %2',
                $status,
                $decoded['message'] ?? $body
            ));
        }

        return $decoded;
    }
}
