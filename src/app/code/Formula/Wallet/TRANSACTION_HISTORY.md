# Wallet Transaction History Implementation

## Overview
This document describes the wallet transaction history feature that tracks all credit and debit transactions for customer wallet balances.

## Database Schema

### Table: `formula_wallet_transaction`
Created via `etc/db_schema.xml`

**Columns:**
- `transaction_id` - Primary key (auto-increment)
- `customer_id` - Customer reference (foreign key to customer_entity)
- `amount` - Transaction amount (absolute value)
- `type` - Transaction type: 'credit' or 'debit'
- `balance_before` - Wallet balance before transaction
- `balance_after` - Wallet balance after transaction
- `description` - Human-readable transaction description
- `reference_type` - Type of transaction: 'order', 'refund', 'admin_api', 'admin_panel'
- `reference_id` - Related entity ID (order_id, creditmemo_id, etc.)
- `created_at` - Transaction timestamp

**Indexes:**
- customer_id
- type
- created_at
- reference_type + reference_id (composite)

## Components

### 1. Data Interface
**File:** `Api/Data/WalletTransactionInterface.php`
- Defines transaction data structure
- Constants for transaction types and reference types

### 2. Model Layer
**Files:**
- `Model/WalletTransaction.php` - Transaction model
- `Model/ResourceModel/WalletTransaction.php` - Resource model
- `Model/ResourceModel/WalletTransaction/Collection.php` - Collection

### 3. Repository
**Files:**
- `Api/WalletTransactionRepositoryInterface.php` - Repository interface
- `Model/WalletTransactionRepository.php` - Repository implementation
- Includes `createTransaction()` helper method for easy transaction creation

### 4. Service Layer
**Files:**
- `Api/WalletManagementInterface.php` - Added `getTransactionHistory()` method
- `Model/WalletManagement.php` - Implemented transaction history retrieval with pagination

## Transaction Logging

### Automatic Transaction Creation

#### 1. Order Placement (DEBIT)
**Observer:** `Observer/OrderPlaceAfter.php`
- Triggers: After order is placed
- Type: DEBIT
- Reference: order (order_id)
- Description: "Wallet payment for order #[increment_id]"

#### 2. Order Cancellation (CREDIT)
**Service:** `OrderCancellationReturn\Service\WalletRefundService.php`
- Triggers: When order is cancelled through OrderCancellationReturn module
- Type: CREDIT
- Reference: order_cancel (order_id)
- Description: "Refund for cancelled order #[increment_id]"
- Adds refund amount to customer wallet

#### 3. Order Return (CREDIT)
**Service:** `OrderCancellationReturn\Service\WalletRefundService.php`
- Triggers: When order is returned through OrderCancellationReturn module
- Type: CREDIT
- Reference: order_return (order_id)
- Description: "Refund for returned order #[increment_id]"
- Adds refund amount to customer wallet

#### 4. Manual Credit Memo (CREDIT)
**Fallback:** Via admin panel credit memo creation
- Triggers: When admin manually creates a credit memo and updates wallet balance
- Type: CREDIT
- Reference: admin_panel
- Captured by CustomerRepositorySavePlugin

#### 5. Admin Adjustments (CREDIT/DEBIT)
**Plugin:** `Plugin/CustomerRepositorySavePlugin.php`
- Triggers: When customer wallet_balance attribute changes (fallback for all other cases)
- Type: Automatically detected (credit if increased, debit if decreased)
- Reference: admin_panel or admin_api (based on area code)
- Description: Dynamic based on change type and source
- **Captures changes from:**
  - Admin panel customer edit form
  - API calls to update wallet balance
  - Any direct customer repository save
  - Manual credit memos (if wallet is updated)

## REST API Endpoints

### Customer Endpoints

#### Get Transaction History (Customer)
```
GET /rest/V1/customers/me/wallet/transactions?pageSize=20&currentPage=1
```
**Authentication:** Customer token required
**Parameters:**
- `pageSize` (optional, default: 20) - Number of transactions per page
- `currentPage` (optional, default: 1) - Page number

**Response:**
```json
{
  "items": [
    {
      "transaction_id": 123,
      "customer_id": 456,
      "amount": 50.00,
      "type": "credit",
      "balance_before": 100.00,
      "balance_after": 150.00,
      "description": "Admin adjustment - wallet credited via admin panel",
      "reference_type": "admin_panel",
      "reference_id": null,
      "created_at": "2025-10-07 12:00:00"
    }
  ],
  "search_criteria": {...},
  "total_count": 45
}
```

### Admin Endpoints

#### Get Transaction History (Admin)
```
GET /rest/V1/customers/:customerId/wallet/transactions?pageSize=20&currentPage=1
```
**Authentication:** Admin token required
**Resource:** `Magento_Customer::manage`

## Configuration

### Dependency Injection (`etc/di.xml`)
- Preference for `WalletTransactionInterface` → `WalletTransaction`
- Preference for `WalletTransactionRepositoryInterface` → `WalletTransactionRepository`
- Plugin on `CustomerRepositoryInterface` → `CustomerRepositorySavePlugin`

### Events (`etc/events.xml`)
- `sales_order_place_after` → `OrderPlaceAfter` observer
- `sales_order_creditmemo_save_after` → `CreditmemoSaveAfter` observer

### Web API (`etc/webapi.xml`)
- Customer transaction history endpoint
- Admin transaction history endpoint

## Setup Instructions

1. **Run setup upgrade:**
   ```bash
   bin/magento setup:upgrade
   ```
   This will create the `formula_wallet_transaction` table from `db_schema.xml`

2. **Generate db_schema_whitelist.json:**
   ```bash
   bin/magento setup:db-declaration:generate-whitelist --module-name=Formula_Wallet
   ```

3. **Clear cache:**
   ```bash
   bin/magento cache:flush
   ```

4. **Compile DI:**
   ```bash
   bin/magento setup:di:compile
   ```

## Transaction Types

### Credit Transactions (Balance Increases)
- Order cancellations (via OrderCancellationReturn module)
- Order returns (via OrderCancellationReturn module)
- Manual credit memos from admin
- Admin manual additions (API or panel)
- Admin panel balance increases

### Debit Transactions (Balance Decreases)
- Order payments using wallet
- Admin manual subtractions (API or panel)
- Admin panel balance decreases

## Reference Types
- `order` - Order placement debit
- `order_cancel` - Order cancellation refund
- `order_return` - Order return refund
- `refund` - Manual credit memo refund
- `admin_panel` - Admin panel adjustment
- `admin_api` - Admin API adjustment

## Features

✅ Complete transaction audit trail
✅ Automatic transaction logging on all balance changes
✅ Pagination support for large transaction histories
✅ Indexed for fast queries
✅ Reference tracking to source orders/credit memos
✅ Human-readable descriptions
✅ Customer and admin API access
✅ No duplicate logging (single plugin captures all customer save operations)

## Notes

- Transactions are logged AFTER the balance change is committed
- The CustomerRepositorySavePlugin has sortOrder=100 to run after other plugins
- All transaction amounts are stored as absolute values
- Type field determines if balance increased (credit) or decreased (debit)
- Transaction history is read-only for customers (no delete operations exposed via API)
