<?php
namespace Formula\RazorpayApi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class SaveRazorpayOrder implements ObserverInterface
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();

        $this->logger->debug('Observer triggered. Order ID: ' . $order->getIncrementId());

        if ($payment && $payment->getMethod() === 'razorpay') {
            $rzpOrderId = $payment->getAdditionalInformation('rzp_order_id');
            $this->logger->debug('Razorpay ID: ' . $rzpOrderId);
        }
    }
}
