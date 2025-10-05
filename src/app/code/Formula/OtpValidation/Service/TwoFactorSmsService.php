<?php
namespace Formula\OtpValidation\Service;

use Formula\OtpValidation\Api\SmsServiceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class TwoFactorSmsService implements SmsServiceInterface
{
    const XML_PATH_2FACTOR_API_KEY = 'formula_otp/twofactor/api_key';
    const XML_PATH_2FACTOR_TEMPLATE_NAME = 'formula_otp/twofactor/template_name';
    const XML_PATH_OTP_MESSAGE_TEMPLATE = 'formula_otp/general/message_template';

    protected $scopeConfig;
    protected $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    public function sendSms($phoneNumber, $message)
    {
        try {
            $apiKey = $this->scopeConfig->getValue(
                self::XML_PATH_2FACTOR_API_KEY,
                ScopeInterface::SCOPE_STORE
            );

            if (!$apiKey) {
                throw new \Exception('2Factor API Key is missing in configuration');
            }

            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            // For general SMS, we'll use the same OTP endpoint
            // 2Factor.in primarily focuses on OTP, so we'll send message as custom text
            $messageResponse = $this->sendViaCurl($apiKey, $formattedPhone, $message);

            if ($messageResponse['success']) {
                $this->logger->info('SMS sent successfully via 2Factor', [
                    'phone' => $formattedPhone,
                    'session_id' => $messageResponse['session_id']
                ]);

                return [
                    'success' => true,
                    'session_id' => $messageResponse['session_id']
                ];
            } else {
                throw new \Exception($messageResponse['error']);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to send SMS via 2Factor', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function sendOtp($phoneNumber, $otpCode)
    {
        try {
            $apiKey = $this->scopeConfig->getValue(
                self::XML_PATH_2FACTOR_API_KEY,
                ScopeInterface::SCOPE_STORE
            );

            if (!$apiKey) {
                throw new \Exception('2Factor API Key is missing in configuration');
            }

            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            // Use 2Factor OTP API
            $messageResponse = $this->sendOtpViaCurl($apiKey, $formattedPhone, $otpCode);

            if ($messageResponse['success']) {
                $this->logger->info('OTP sent successfully via 2Factor', [
                    'phone' => $formattedPhone,
                    'session_id' => $messageResponse['session_id']
                ]);

                return [
                    'success' => true,
                    'session_id' => $messageResponse['session_id']
                ];
            } else {
                throw new \Exception($messageResponse['error']);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to send OTP via 2Factor', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // If it's a 10-digit number starting with 6-9, add 91 prefix
        if (strlen($phoneNumber) === 10 && preg_match('/^[6-9]/', $phoneNumber)) {
            return '91' . $phoneNumber;
        }

        // If it already has 91 prefix and is 12 digits, use as is
        if (strpos($phoneNumber, '91') === 0 && strlen($phoneNumber) === 12) {
            return $phoneNumber;
        }

        // If it starts with +91, remove the +
        if (strpos($phoneNumber, '+91') === 0) {
            return substr($phoneNumber, 1);
        }

        throw new \InvalidArgumentException('Invalid Indian phone number format');
    }

    public function isValidIndianMobile($phoneNumber)
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Valid 10-digit Indian mobile number
        if (strlen($phoneNumber) === 10 && preg_match('/^[6-9][0-9]{9}$/', $phoneNumber)) {
            return true;
        }

        // Valid 12-digit with 91 country code
        if (strlen($phoneNumber) === 12 && preg_match('/^91[6-9][0-9]{9}$/', $phoneNumber)) {
            return true;
        }

        return false;
    }

    protected function sendOtpViaCurl($apiKey, $toNumber, $otpCode)
    {
        try {
            $templateName = $this->scopeConfig->getValue(
                self::XML_PATH_2FACTOR_TEMPLATE_NAME,
                ScopeInterface::SCOPE_STORE
            );

            // Build the URL - 2Factor uses URL parameters for OTP sending
            $url = "https://2factor.in/API/V1/{$apiKey}/SMS/{$toNumber}/{$otpCode}";

            // Add optional template parameter if configured
            if ($templateName) {
                $url .= "/" . urlencode($templateName);
            }

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            // Log the response for debugging
            $this->logger->info('2Factor API Response', [
                'http_code' => $httpCode,
                'response' => $response,
                'curl_error' => $curlError,
                'phone' => $toNumber,
                'otp_length' => strlen($otpCode)
            ]);

            if ($curlError) {
                return [
                    'success' => false,
                    'error' => 'cURL Error: ' . $curlError
                ];
            }

            if ($httpCode === 200) {
                $responseData = json_decode($response, true);

                if ($responseData && isset($responseData['Status'])) {
                    if ($responseData['Status'] === 'Success') {
                        return [
                            'success' => true,
                            'session_id' => $responseData['Details'] ?? '2FACTOR-' . time()
                        ];
                    } else {
                        return [
                            'success' => false,
                            'error' => $responseData['Details'] ?? 'Unknown 2Factor API error'
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'error' => '2Factor API returned invalid response: ' . $response
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP error: ' . $httpCode . ' Response: ' . $response
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to connect to 2Factor API: ' . $e->getMessage()
            ];
        }
    }

    protected function sendViaCurl($apiKey, $toNumber, $message)
    {
        try {
            // 2Factor.in is primarily designed for OTP
            // For general SMS, we can use their transactional SMS endpoint
            $url = "https://2factor.in/API/V1/{$apiKey}/ADDON_SERVICES/SEND/TSMS";

            $postData = [
                'From' => 'FORMLA', // 6-character sender ID - update as per your registered sender
                'To' => $toNumber,
                'Msg' => $message
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            // Log the response for debugging
            $this->logger->info('2Factor Transactional SMS Response', [
                'http_code' => $httpCode,
                'response' => $response,
                'curl_error' => $curlError,
                'phone' => $toNumber
            ]);

            if ($curlError) {
                return [
                    'success' => false,
                    'error' => 'cURL Error: ' . $curlError
                ];
            }

            if ($httpCode === 200) {
                $responseData = json_decode($response, true);

                if ($responseData && isset($responseData['Status'])) {
                    if ($responseData['Status'] === 'Success') {
                        return [
                            'success' => true,
                            'session_id' => $responseData['Details'] ?? '2FACTOR-SMS-' . time()
                        ];
                    } else {
                        return [
                            'success' => false,
                            'error' => $responseData['Details'] ?? 'Unknown 2Factor error'
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'error' => '2Factor API returned invalid response: ' . $response
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP error: ' . $httpCode . ' Response: ' . $response
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to connect to 2Factor: ' . $e->getMessage()
            ];
        }
    }
}
