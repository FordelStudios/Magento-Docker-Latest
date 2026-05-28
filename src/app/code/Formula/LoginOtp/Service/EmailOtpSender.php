<?php
declare(strict_types=1);

namespace Formula\LoginOtp\Service;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Sends a recovery OTP via email (Magento's transactional mail pipeline).
 *
 * Uses a fixed transactional template `formula_email_recovery_otp` that we
 * register via etc/email_templates.xml. Variables: { otp, expires_in_minutes }.
 */
class EmailOtpSender
{
    public const TEMPLATE_ID = 'formula_email_recovery_otp';

    private TransportBuilder $transportBuilder;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;

    public function __construct(
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @return array{success: bool, error?: string}
     */
    public function send(string $email, string $otp, int $expiryMinutes): array
    {
        try {
            $store = $this->storeManager->getStore();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier(self::TEMPLATE_ID)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ])
                ->setTemplateVars([
                    'otp' => $otp,
                    'expires_in_minutes' => $expiryMinutes,
                    'store_name' => $store->getName(),
                ])
                ->setFromByScope('general', $store->getId())
                ->addTo($email)
                ->getTransport();

            $transport->sendMessage();
            return ['success' => true];
        } catch (MailException $e) {
            $this->logger->error('Formula\LoginOtp email OTP send failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Could not send email. Please try again.'];
        } catch (\Throwable $e) {
            $this->logger->error('Formula\LoginOtp email OTP unexpected failure', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Could not send email. Please try again.'];
        }
    }
}
