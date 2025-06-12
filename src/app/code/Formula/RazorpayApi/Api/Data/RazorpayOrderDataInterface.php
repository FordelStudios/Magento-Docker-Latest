<?php
namespace Formula\RazorpayApi\Api\Data;

interface RazorpayOrderDataInterface
{
    /**
     * Get Razorpay Order ID
     *
     * @return string
     */
    public function getRazorpayOrderId(): string;

    /**
     * Set Razorpay Order ID
     *
     * @param string $id
     * @return $this
     */
    public function setRazorpayOrderId(string $id): self;

    /**
     * Get Amount in smallest currency unit
     *
     * @return int
     */
    public function getAmount(): int;

    /**
     * Set Amount in smallest currency unit
     *
     * @param int $amount
     * @return $this
     */
    public function setAmount(int $amount): self;

    /**
     * Get Currency code
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Set Currency code
     *
     * @param string $currency
     * @return $this
     */
    public function setCurrency(string $currency): self;

    /**
     * Get Razorpay Public API Key
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Set Razorpay Public API Key
     *
     * @param string $key
     * @return $this
     */
    public function setKey(string $key): self;
}
