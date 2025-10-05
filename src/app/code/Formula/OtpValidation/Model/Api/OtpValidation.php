<?php
namespace Formula\OtpValidation\Model\Api;

use Formula\OtpValidation\Api\Data\SendOtpResponseInterface;
use Formula\OtpValidation\Api\Data\VerifyOtpResponseInterface;
use Formula\OtpValidation\Api\OtpValidationInterface;
use Formula\OtpValidation\Model\Data\SendOtpResponse;
use Formula\OtpValidation\Model\Data\VerifyOtpResponse;
use Formula\OtpValidation\Service\OtpService;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Authorization\Model\UserContextInterface;

class OtpValidation implements OtpValidationInterface
{
    protected $otpService;
    protected $userContext;
    protected $request;
    protected $customerRepository;

    public function __construct(
        OtpService $otpService,
        UserContextInterface $userContext,
        Request $request,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->otpService = $otpService;
        $this->userContext = $userContext;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
    }

    public function sendOtp($phoneNumber)
    {
        $response = new SendOtpResponse();

        try {
            $customerId = $this->userContext->getUserId();

            if (!$customerId) {
                throw new LocalizedException(__('Customer not authenticated'));
            }

            $bodyParams = $this->request->getBodyParams();
            $phoneNumber = $bodyParams['phone_number'] ?? $phoneNumber;

            if (empty($phoneNumber)) {
                throw new LocalizedException(__('Phone number is required'));
            }

            $result = $this->otpService->generateAndSendOtp($customerId, $phoneNumber);

            $response->setSuccess(true);
            $response->setMessage($result['message']);
            $response->setExpiresInMinutes($result['expires_in_minutes']);

            return $response;

        } catch (LocalizedException $e) {
            $response->setSuccess(false);
            $response->setMessage($e->getMessage());
            $response->setErrorCode('VALIDATION_ERROR');

            return $response;
        } catch (\Exception $e) {
            $response->setSuccess(false);
            $response->setMessage('An error occurred while sending OTP');
            $response->setErrorCode('SYSTEM_ERROR');

            return $response;
        }
    }

    public function verifyOtp($phoneNumber, $otpCode)
    {
        $response = new VerifyOtpResponse();

        try {
            $customerId = $this->userContext->getUserId();

            if (!$customerId) {
                throw new LocalizedException(__('Customer not authenticated'));
            }

            $bodyParams = $this->request->getBodyParams();
            $phoneNumber = $bodyParams['phone_number'] ?? $phoneNumber;
            $otpCode = $bodyParams['otp_code'] ?? $otpCode;

            if (empty($phoneNumber)) {
                throw new LocalizedException(__('Phone number is required'));
            }

            if (empty($otpCode)) {
                throw new LocalizedException(__('OTP code is required'));
            }

            $result = $this->otpService->verifyOtp($customerId, $phoneNumber, $otpCode);

            $response->setSuccess(true);
            $response->setMessage($result['message']);
            $response->setVerified(true);

            return $response;

        } catch (LocalizedException $e) {
            $response->setSuccess(false);
            $response->setMessage($e->getMessage());
            $response->setVerified(false);
            $response->setErrorCode('VALIDATION_ERROR');

            return $response;
        } catch (\Exception $e) {
            $response->setSuccess(false);
            $response->setMessage('An error occurred while verifying OTP');
            $response->setVerified(false);
            $response->setErrorCode('SYSTEM_ERROR');

            return $response;
        }
    }
}