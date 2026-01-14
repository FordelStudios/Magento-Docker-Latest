<?php

declare(strict_types=1);

namespace Formula\OrderCancellationReturn\Service;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class ReturnEmailSender
{
    const TEMPLATE_RETURN_REQUESTED = 'formula_return_requested';
    const TEMPLATE_RETURN_APPROVED = 'formula_return_approved';
    const TEMPLATE_RETURN_REJECTED = 'formula_return_rejected';
    const TEMPLATE_REFUND_COMPLETED = 'formula_refund_completed';

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

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
     * Send return request confirmation email
     *
     * @param Order $order
     * @param string $reason
     * @return bool
     */
    public function sendReturnRequestedEmail(Order $order, string $reason = ''): bool
    {
        return $this->sendEmail(
            self::TEMPLATE_RETURN_REQUESTED,
            $order,
            [
                'return_reason' => $reason ?: 'Not specified',
                'request_date' => date('F j, Y, g:i a'),
            ]
        );
    }

    /**
     * Send return approved email
     *
     * @param Order $order
     * @param string $pickupDetails
     * @return bool
     */
    public function sendReturnApprovedEmail(Order $order, string $pickupDetails = ''): bool
    {
        return $this->sendEmail(
            self::TEMPLATE_RETURN_APPROVED,
            $order,
            [
                'pickup_details' => $pickupDetails,
            ]
        );
    }

    /**
     * Send return rejected email
     *
     * @param Order $order
     * @param string $rejectionReason
     * @return bool
     */
    public function sendReturnRejectedEmail(Order $order, string $rejectionReason = ''): bool
    {
        return $this->sendEmail(
            self::TEMPLATE_RETURN_REJECTED,
            $order,
            [
                'rejection_reason' => $rejectionReason ?: 'Return request does not meet our return policy requirements.',
            ]
        );
    }

    /**
     * Send refund completed email
     *
     * @param Order $order
     * @param float $refundAmount
     * @param string $refundMethod
     * @return bool
     */
    public function sendRefundCompletedEmail(Order $order, float $refundAmount, string $refundMethod): bool
    {
        return $this->sendEmail(
            self::TEMPLATE_REFUND_COMPLETED,
            $order,
            [
                'refund_amount' => number_format($refundAmount, 2),
                'refund_method' => $refundMethod,
            ]
        );
    }

    /**
     * Send email using template
     *
     * @param string $templateId
     * @param Order $order
     * @param array $additionalVars
     * @return bool
     */
    private function sendEmail(string $templateId, Order $order, array $additionalVars = []): bool
    {
        try {
            $customerEmail = $order->getCustomerEmail();
            $customerName = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
            $storeId = $order->getStoreId();

            $store = $this->storeManager->getStore($storeId);

            $templateVars = array_merge([
                'order' => $order,
                'order_id' => $order->getIncrementId(),
                'customer_name' => $customerName,
                'store' => $store,
            ], $additionalVars);

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope('general', $storeId)
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();

            $this->logger->info(sprintf(
                'Return email sent: template=%s, order=%s, customer=%s',
                $templateId,
                $order->getIncrementId(),
                $customerEmail
            ));

            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Failed to send return email: template=%s, order=%s, error=%s',
                $templateId,
                $order->getIncrementId(),
                $e->getMessage()
            ));
            return false;
        }
    }
}
