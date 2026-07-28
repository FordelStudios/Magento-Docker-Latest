<?php
declare(strict_types=1);

namespace Formula\ZohoBooks\Test\Unit\Model\Mapper;

use Formula\ZohoBooks\Model\Gst\StateCodeMapper;
use Formula\ZohoBooks\Model\Mapper\ContactMapper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContactMapperTest extends TestCase
{
    private ContactMapper $mapper;

    protected function setUp(): void
    {
        // StateCodeMapper is pure/cheap - use the real implementation for a more
        // realistic end-to-end assertion on place_of_contact.
        $this->mapper = new ContactMapper(new StateCodeMapper());
    }

    private function makeBillingAddress(array $overrides = []): OrderAddressInterface|MockObject
    {
        $address = $this->createMock(OrderAddressInterface::class);
        $defaults = [
            'getFirstname' => 'Priya',
            'getLastname' => 'Sharma',
            'getCompany' => null,
            'getStreet' => ['221B, Sunshine Apartments', 'Bandra West'],
            'getCity' => 'Mumbai',
            'getRegion' => 'Maharashtra',
            'getRegionCode' => 'MH',
            'getPostcode' => '400050',
            'getCountryId' => 'IN',
            'getTelephone' => '9876543210',
        ];
        $values = array_merge($defaults, $overrides);
        foreach ($values as $method => $value) {
            $address->method($method)->willReturn($value);
        }

        return $address;
    }

    private function makeOrder(?OrderAddressInterface $billingAddress, string $email = 'priya@example.com'): OrderInterface|MockObject
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getBillingAddress')->willReturn($billingAddress);
        $order->method('getCustomerEmail')->willReturn($email);
        $order->method('getIncrementId')->willReturn('000000123');

        return $order;
    }

    public function testBuildReturnsExpectedContactPayload(): void
    {
        $order = $this->makeOrder($this->makeBillingAddress());

        $payload = $this->mapper->build($order);

        $this->assertSame('Priya Sharma', $payload['contact_name']);
        $this->assertNull($payload['company_name']);
        $this->assertSame(
            [
                [
                    'first_name' => 'Priya',
                    'last_name' => 'Sharma',
                    'email' => 'priya@example.com',
                    'phone' => '9876543210',
                ],
            ],
            $payload['contact_persons']
        );
        $this->assertSame(
            [
                'address' => '221B, Sunshine Apartments, Bandra West',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'zip' => '400050',
                'country' => 'IN',
            ],
            $payload['billing_address']
        );
        $this->assertSame(ContactMapper::GST_TREATMENT_CONSUMER, $payload['gst_treatment']);
        $this->assertSame('27', $payload['place_of_contact']);
    }

    public function testBuildIncludesCompanyNameWhenPresent(): void
    {
        $order = $this->makeOrder($this->makeBillingAddress(['getCompany' => 'Formula Pvt Ltd']));

        $payload = $this->mapper->build($order);

        $this->assertSame('Formula Pvt Ltd', $payload['company_name']);
    }

    public function testBuildFallsBackToEmailWhenNameIsBlank(): void
    {
        $order = $this->makeOrder($this->makeBillingAddress(['getFirstname' => '', 'getLastname' => '']));

        $payload = $this->mapper->build($order);

        $this->assertSame('priya@example.com', $payload['contact_name']);
    }

    public function testBuildResolvesPlaceOfContactFromRegionCode(): void
    {
        $order = $this->makeOrder($this->makeBillingAddress(['getRegionCode' => 'DL', 'getRegion' => 'Delhi']));

        $payload = $this->mapper->build($order);

        $this->assertSame('07', $payload['place_of_contact']);
    }

    public function testBuildThrowsWhenOrderHasNoBillingAddress(): void
    {
        $order = $this->makeOrder(null);

        $this->expectException(LocalizedException::class);

        $this->mapper->build($order);
    }

    public function testBuildThrowsWhenBillingRegionIsUnrecognized(): void
    {
        $order = $this->makeOrder($this->makeBillingAddress(['getRegionCode' => 'ZZ', 'getRegion' => 'Nowhere']));

        $this->expectException(LocalizedException::class);

        $this->mapper->build($order);
    }
}
