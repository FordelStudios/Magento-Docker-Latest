<?php
namespace Formula\Wallet\Block\Adminhtml\Order\Totals;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;

class Wallet extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get current order
     *
     * @return Order
     */
    public function getOrder()
    {
        if (!$this->order) {
            $this->order = $this->registry->registry('current_order');
        }
        return $this->order;
    }

    /**
     * Get wallet amount used in order
     *
     * @return float
     */
    public function getWalletAmountUsed()
    {
        $order = $this->getOrder();
        return $order ? (float)$order->getWalletAmountUsed() : 0;
    }

    /**
     * Get formatted wallet amount
     *
     * @return string
     */
    public function getFormattedWalletAmount()
    {
        $order = $this->getOrder();
        if (!$order) {
            return '';
        }
        
        return $order->formatPrice($this->getWalletAmountUsed());
    }

    /**
     * Check if wallet was used in this order
     *
     * @return bool
     */
    public function hasWalletAmount()
    {
        return $this->getWalletAmountUsed() > 0;
    }
}