<?php
namespace Formula\RazorpayApi\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ResourceConnection;
use Formula\RazorpayApi\Api\RazorpayOrderInterface;
use Formula\RazorpayApi\Api\Data\RazorpayOrderDataInterface;

class RazorpayOrder implements RazorpayOrderInterface
{
    protected $orderRepository;
    protected $resource;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ResourceConnection $resource,
        \Formula\RazorpayApi\Model\Data\RazorpayOrderDataFactory $razorpayOrderDataFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->resource = $resource;
        $this->razorpayOrderDataFactory = $razorpayOrderDataFactory;
    }

    public function getByIncrementId(string $incrementId): RazorpayOrderDataInterface
    {
        try {
            $order = $this->orderRepository->get($incrementId);
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('razorpay_sales_order');

            $select = $connection->select()
                ->from($table)
                ->where('order_id = ?', $order->getEntityId());

            $row = $connection->fetchRow($select);

            if (!$row || !isset($row['rzp_order_id'])) {
                throw new NoSuchEntityException(__('Razorpay order not found.'));
            }

            $model = $this->razorpayOrderDataFactory->create();

            $model->setRazorpayOrderId($row['rzp_order_id'])
                ->setAmount((int) ($order->getGrandTotal() * 100))
                ->setCurrency($order->getOrderCurrencyCode())
                ->setKey('rzp_test_X7LgvyRx65km1S');

            return $model;
        } catch (\Exception $e) {
            throw new NoSuchEntityException(__($e->getMessage()));
        }
    }
}
