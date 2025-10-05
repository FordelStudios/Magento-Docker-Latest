<?php
namespace Formula\OtpValidation\Service;

use Formula\OtpValidation\Api\SmsServiceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Msg91SmsService implements SmsServiceInterface
{
    const XML_PATH_MSG91_API_KEY = 'formula_otp/msg91/api_key';
    const XML_PATH_MSG91_SENDER_ID = 'formula_otp/msg91/sender_id';
    const XML_PATH_MSG91_ROUTE = 'formula_otp/msg91/route';
    const XML_PATH_MSG91_DLT_TEMPLATE_ID = 'formula_otp/msg91/dlt_template_id';
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
                self::XML_PATH_MSG91_API_KEY,
                ScopeInterface::SCOPE_STORE
            );
            $senderId = $this->scopeConfig->getValue(
                self::XML_PATH_MSG91_SENDER_ID,
                ScopeInterface::SCOPE_STORE
            );
            $route = $this->scopeConfig->getValue(
                self::XML_PATH_MSG91_ROUTE,
                ScopeInterface::SCOPE_STORE
            ) ?: '4';
            if (!$apiKey || !$senderId) {
                throw new \Exception('MSG91 configuration is missing');
            }

            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            $messageResponse = $this->sendViaCurl($apiKey, $senderId, $formattedPhone, $message);

            if ($messageResponse['success']) {
                $this->logger->info('OTP SMS sent successfully via MSG91', [
                    'phone' => $formattedPhone,
                    'request_id' => $messageResponse['request_id'],
                    'dlt_template_id' => $dltTemplateId
                ]);

                return [
                    'success' => true,
                    'message_id' => $messageResponse['request_id']
                ];
            } else {
                throw new \Exception($messageResponse['error']);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to send OTP SMS via MSG91', [
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
                self::XML_PATH_MSG91_API_KEY,
                ScopeInterface::SCOPE_STORE
            );
            $senderId = $this->scopeConfig->getValue(
                self::XML_PATH_MSG91_SENDER_ID,
                ScopeInterface::SCOPE_STORE
            );

            if (!$apiKey || !$senderId) {
                throw new \Exception('MSG91 configuration is missing');
            }

            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            // Use OTP API specifically for OTP messages
            $messageResponse = $this->sendOtpViaCurl($apiKey, $senderId, $formattedPhone, $otpCode);

            if ($messageResponse['success']) {
                $this->logger->info('OTP sent successfully via MSG91 OTP API', [
                    'phone' => $formattedPhone,
                    'request_id' => $messageResponse['request_id']
                ]);

                return [
                    'success' => true,
                    'message_id' => $messageResponse['request_id']
                ];
            } else {
                throw new \Exception($messageResponse['error']);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to send OTP via MSG91 OTP API', [
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
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (strlen($phoneNumber) === 10 && preg_match('/^[6-9]/', $phoneNumber)) {
            return '91' . $phoneNumber;
        }

        if (strpos($phoneNumber, '91') === 0 && strlen($phoneNumber) === 12) {
            return $phoneNumber;
        }

        if (strpos($phoneNumber, '+91') === 0) {
            return substr($phoneNumber, 1);
        }

        throw new \InvalidArgumentException('Invalid Indian phone number format');
    }

    public function isValidIndianMobile($phoneNumber)
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (strlen($phoneNumber) === 10 && preg_match('/^[6-9][0-9]{9}$/', $phoneNumber)) {
            return true;
        }

        if (strlen($phoneNumber) === 12 && preg_match('/^91[6-9][0-9]{9}$/', $phoneNumber)) {
            return true;
        }

        return false;
    }

    protected function sendOtpViaCurl($apiKey, $senderId, $toNumber, $otpCode)
    {
        try {
            $url = "https://api.msg91.com/api/v5/otp";

            $postFields = [
                'authkey' => $apiKey,
                'mobile' => $toNumber,
                'sender' => $senderId,
                'otp' => $otpCode
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postFields),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded'
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            // Log the raw response for debugging
            $this->logger->info('MSG91 OTP API Response', [
                'http_code' => $httpCode,
                'response' => $response,
                'curl_error' => $curlError,
                'phone' => $toNumber,
                'sender' => $senderId,
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

                if ($responseData && isset($responseData['type'])) {
                    if ($responseData['type'] === 'success') {
                        return [
                            'success' => true,
                            'request_id' => $responseData['request_id'] ?? 'MSG91-OTP-' . time()
                        ];
                    } else {
                        return [
                            'success' => false,
                            'error' => $responseData['message'] ?? 'Unknown MSG91 OTP API error'
                        ];
                    }
                } else {
                    // Handle non-JSON responses
                    if (strpos($response, 'success') !== false) {
                        return [
                            'success' => true,
                            'request_id' => 'MSG91-OTP-' . time()
                        ];
                    } else {
                        return [
                            'success' => false,
                            'error' => 'MSG91 OTP API Error: ' . $response
                        ];
                    }
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
                'error' => 'Failed to connect to MSG91 OTP API: ' . $e->getMessage()
            ];
        }
    }

    protected function sendViaCurl($apiKey, $senderId, $toNumber, $message)
    {
        try {
            $url = "https://control.msg91.com/api/sendhttp.php";

            $postFields = [
                'authkey' => $apiKey,
                'mobiles' => $toNumber,
                'message' => $message,
                'sender' => $senderId,
                'route' => '4'
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postFields),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded'
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            // Log the raw response for debugging
            $this->logger->info('MSG91 SMS API Response', [
                'http_code' => $httpCode,
                'response' => $response,
                'curl_error' => $curlError,
                'phone' => $toNumber,
                'sender' => $senderId
            ]);

            if ($curlError) {
                return [
                    'success' => false,
                    'error' => 'cURL Error: ' . $curlError
                ];
            }

            if ($httpCode === 200) {
                // MSG91 returns different response formats
                // Sometimes it's JSON, sometimes plain text with message ID
                $responseData = json_decode($response, true);

                if ($responseData) {
                    // JSON response
                    if (isset($responseData['type']) && $responseData['type'] === 'success') {
                        return [
                            'success' => true,
                            'request_id' => $responseData['message'] ?? 'MSG91-' . time()
                        ];
                    } else {
                        return [
                            'success' => false,
                            'error' => $responseData['message'] ?? 'Unknown MSG91 error'
                        ];
                    }
                } else {
                    // Plain text response - usually contains message ID if successful
                    if (preg_match('/^[a-zA-Z0-9-]+$/', trim($response))) {
                        return [
                            'success' => true,
                            'request_id' => trim($response)
                        ];
                    } else {
                        // Handle specific error codes
                        $errorMessage = $this->parseDltErrorMessage(trim($response));
                        return [
                            'success' => false,
                            'error' => $errorMessage
                        ];
                    }
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
                'error' => 'Failed to connect to MSG91: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parse MSG91 error messages and provide user-friendly error descriptions
     *
     * @param string $response
     * @return string
     */
    protected function parseDltErrorMessage($response)
    {
        // Common MSG91 error codes and their meanings
        $errorCodes = [
            '211' => 'DLT Template ID not found or invalid. Please check your DLT Template ID in admin configuration.',
            '418' => 'IP address not whitelisted. Please add your server IP to MSG91 whitelist.',
            '400' => 'Invalid request format or missing required parameters.',
            '401' => 'Invalid API key. Please check your MSG91 API key.',
            '402' => 'Message contains blocked keywords.',
            '403' => 'SMS route is disabled.',
            '421' => 'MSG91 service terminated. Please contact MSG91 support.',
            '506' => 'Internal error at MSG91. Please contact your account manager.',
            '601' => 'Internal error at MSG91. Please contact your account manager.',
            '602' => 'Selected route is disabled. Please select another route.',
            // OTP API specific errors
            '1025' => 'Mobile number is invalid or not in correct format.',
            '1026' => 'OTP length should be between 4-9 digits.',
            '1027' => 'Invalid sender ID format.',
            '1028' => 'OTP template not found or expired.'
        ];

        // Check if response contains error code
        foreach ($errorCodes as $code => $description) {
            if (strpos($response, $code) !== false) {
                return "MSG91 Error {$code}: {$description}";
            }
        }

        // If no specific error code found, return the raw response
        return 'MSG91 Error: ' . $response;
    }
}