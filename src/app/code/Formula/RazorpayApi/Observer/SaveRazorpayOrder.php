<?php
namespace Formula\RazorpayApi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\ResourceConnection;

class SaveRazorpayOrder implements ObserverInterface
{
    protected $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();

        if ($payment && $payment->getMethod() === 'razorpay') {
            $rzpOrderId = $payment->getAdditionalInformation('rzp_order_id');

            if ($rzpOrderId) {
                $connection = $this->resource->getConnection();
                $table = $this->resource->getTableName('razorpay_sales_order');

                $connection->insertOnDuplicate($table, [
                    'order_id' => $order->getEntityId(),
                    'rzp_order_id' => $rzpOrderId,
                ]);
            }
        }
    }
}
