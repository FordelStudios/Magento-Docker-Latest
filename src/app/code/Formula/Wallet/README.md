# Formula Wallet Module

## Overview
The Formula_Wallet module provides a complete customer wallet functionality for Magento 2.3.7-p3, allowing customers to use their wallet balance for full or partial payments during checkout. The module is designed to work with React.js frontend applications through REST API endpoints.

## Features

### Customer Features
- View wallet balance via REST API
- Apply wallet balance to cart (partial or full payment)
- Remove wallet balance from cart
- Place orders using wallet balance only
- Automatic wallet balance deduction after successful order placement

### Admin Features
- Manage customer wallet balance from Customer Edit page
- View wallet usage in order details
- Update wallet balance via admin API
- Complete transaction logging

### Technical Features
- REST API endpoints for React frontend integration
- Custom payment method for wallet-only payments
- Quote and order total calculations
- Database tracking of wallet usage
- Extension attributes for quotes and orders

## REST API Endpoints

### Customer Endpoints (Requires Customer Authentication)

#### Get Wallet Balance
```
GET /rest/V1/customers/me/wallet
```
Response:
```json
{
  "balance": 150.50
}
```

#### Apply Wallet to Cart
```
POST /rest/V1/carts/mine/wallet/apply
```
Body:
```json
{
  "amount": 50.00  // Optional - uses full balance if not specified
}
```

#### Remove Wallet from Cart
```
DELETE /rest/V1/carts/mine/wallet/remove
```

#### Place Order with Wallet
```
POST /rest/V1/wallet/place-order
```
Body:
```json
{
  "cartId": 123
}
```

### Admin Endpoints (Requires Admin Authentication)

#### Get Customer Wallet Balance
```
GET /rest/V1/customers/{customerId}/wallet
```

#### Update Customer Wallet Balance
```
PUT /rest/V1/customers/{customerId}/wallet
```
Body:
```json
{
  "amount": 100.00,
  "action": "add"  // Options: "add", "subtract", "set"
}
```

## Installation

1. Copy the module files to `app/code/Formula/Wallet/`
2. Run the following commands:
```bash
php bin/magento module:enable Formula_Wallet
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

## Database Changes

### Customer Attribute
- `wallet_balance` - Decimal field to store customer wallet balance

### Quote Table
- `wallet_amount_used` - Decimal field for wallet amount applied to quote
- `base_wallet_amount_used` - Base currency wallet amount

### Order Table
- `wallet_amount_used` - Decimal field for wallet amount used in order
- `base_wallet_amount_used` - Base currency wallet amount

## Usage Examples

### React Frontend Integration

```javascript
// Get wallet balance
const getWalletBalance = async () => {
  const response = await fetch('/rest/V1/customers/me/wallet', {
    headers: {
      'Authorization': `Bearer ${customerToken}`
    }
  });
  return response.json();
};

// Apply wallet to cart
const applyWallet = async (amount) => {
  const response = await fetch('/rest/V1/carts/mine/wallet/apply', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${customerToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ amount })
  });
  return response.json();
};

// Place order with wallet
const placeOrderWithWallet = async (cartId) => {
  const response = await fetch('/rest/V1/wallet/place-order', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${customerToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ cartId })
  });
  return response.json();
};
```

## Configuration

### Payment Method Configuration
The wallet payment method is automatically configured and will only appear when:
1. Customer has sufficient wallet balance
2. Cart total is covered by available wallet balance

### Admin Configuration
1. Navigate to Admin > Customers > All Customers
2. Edit any customer
3. Find "Wallet Balance" field in Account Information section
4. Set/update the wallet balance as needed

## Error Handling

The module includes comprehensive error handling for:
- Insufficient wallet balance
- Invalid cart access
- Customer authentication issues
- Database transaction failures

All errors are logged and return appropriate HTTP status codes with descriptive messages.

## Logging

The module logs important events including:
- Wallet balance updates
- Order placement with wallet
- Error conditions

Logs can be found in `var/log/system.log` and `var/log/debug.log`.

## Compatibility

- Magento 2.3.7-p3 Open Source
- PHP 7.2+
- MySQL 5.7+
- Compatible with React.js frontends
- Works alongside existing payment methods (Razorpay, Cash on Delivery)

## Support

For issues and questions, please check the module logs first. Common issues include:
- Module not enabled: Run `php bin/magento module:status`
- Database not updated: Run `php bin/magento setup:upgrade`
- API authentication: Ensure customer tokens are valid