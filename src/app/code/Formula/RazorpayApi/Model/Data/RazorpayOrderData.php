<?php
namespace Formula\RazorpayApi\Model\Data;

use Formula\RazorpayApi\Api\Data\RazorpayOrderDataInterface;
use Magento\Framework\Model\AbstractModel;

class RazorpayOrderData extends AbstractModel implements RazorpayOrderDataInterface
{
    const RZP_ORDER_ID = 'razorpay_order_id';
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';


    public function getRazorpayOrderId(): string
    {
        return $this->getData(self::RZP_ORDER_ID);
    }

    public function setRazorpayOrderId(string $id): self
    {
        return $this->setData(self::RZP_ORDER_ID, $id);
    }

    public function getAmount(): int
    {
        return $this->getData(self::AMOUNT);
    }

    public function setAmount(int $amount): self
    {
        return $this->setData(self::AMOUNT, $amount);
    }

    public function getCurrency(): string
    {
        return $this->getData(self::CURRENCY);
    }

    public function setCurrency(string $currency): self
    {
        return $this->setData(self::CURRENCY, $currency);
    }

}
