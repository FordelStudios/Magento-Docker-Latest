# Wallet Balance Update Fix

## Problem
After implementing transaction history, wallet balance was **not updating** when orders were placed or refunded, even though transaction history records were being created correctly.

**Symptom:**
- Transaction history shows: `balance_before: 900` → `balance_after: 400`
- But actual wallet balance query returns: `900` (unchanged)

## Root Cause

There were **TWO interfering plugins**:

### 1. CustomerRepositorySavePlugin (Transaction Logging)
Was intercepting ALL customer saves and re-fetching from database, causing EAV cache conflicts.

### 2. CustomerRepositoryPlugin (Security - MAIN CULPRIT!)
**This was the actual problem!** Located at `/src/app/code/Formula/Wallet/Plugin/CustomerRepositoryPlugin.php`

This plugin's purpose: Prevent customers from manipulating their own wallet balance through profile updates.

**What it was doing:**
1. Running on ALL `webapi_rest` area customer saves (line 74)
2. Fetching original wallet balance from database (line 96)
3. **FORCEFULLY OVERWRITING** any wallet_balance changes back to original value (line 100)
4. This happened during order placement, cancellation, and refunds!

**The Evidence:**
```
[09:20:43] main.INFO: Wallet balance preserved ... preserved_balance: 900
[09:20:44] main.INFO: Wallet amount deducted ... old_balance:900, new_balance:400
```

The plugin was "preserving" (resetting) the balance to 900 **before** the order deduction could persist!

**Result:**
- Transaction logged with correct values (because it read them before the reset)
- But actual balance was reset back to original
- Customer sees: transaction shows 900→400, but balance stays at 900

## Solution

### 1. Updated `CustomerRepositoryPlugin` (/src/app/code/Formula/Wallet/Plugin/CustomerRepositoryPlugin.php)

**THE CRITICAL FIX:**
Added registry flag check at line 100-103:
```php
// Check if a legitimate wallet operation is in progress
if ($this->registry->registry('wallet_balance_update_in_progress')) {
    return [$customer, $passwordHash];  // Allow the update!
}
```

**How it works:**
- Plugin still prevents customer profile updates from changing wallet balance
- BUT now checks for `wallet_balance_update_in_progress` registry flag
- If flag is set, plugin allows the wallet balance update to proceed
- This flag is set by legitimate wallet operations (order placement, refunds)

### 2. Updated `CustomerRepositorySavePlugin` (/src/app/code/Formula/Wallet/Plugin/CustomerRepositorySavePlugin.php)

**CRITICAL FIX #2 - Prevent Duplicate Transactions:**
Added registry flag check in `shouldLogTransaction()`:
```php
// If wallet operation is in progress, another service handles transaction logging
if ($this->registry->registry('wallet_balance_update_in_progress')) {
    return false;  // Don't create duplicate transaction!
}
```

**Why this is needed:**
- Order placement happens in `webapi_rest` area (customer API call)
- Plugin was thinking: "webapi_rest = admin API call" → creating duplicate transaction
- Now plugin checks: "Is wallet operation in progress? Then OrderPlaceAfter/WalletRefundService is handling it"
- Plugin only logs for TRUE admin updates (when no flag is set)

**Result:**
- No duplicate "Admin adjustment via API" transactions
- Single transaction per operation

### 3. Updated `OrderPlaceAfter` Observer (/src/app/code/Formula/Wallet/Observer/OrderPlaceAfter.php)

**Added registry flag:**
```php
// Set registry flag before save
$this->registry->register('wallet_balance_update_in_progress', true, true);
$this->customerRepository->save($customer);
// Unregister after save
$this->registry->unregister('wallet_balance_update_in_progress');
```

This signals to `CustomerRepositoryPlugin` that this is a legitimate wallet operation.

### 4. Updated `WalletRefundService` (/src/app/code/Formula/OrderCancellationReturn/Service/WalletRefundService.php)

**Added same registry flag:**
```php
$this->registry->register('wallet_balance_update_in_progress', true, true);
$this->customerRepository->save($customer);
$this->registry->unregister('wallet_balance_update_in_progress');
```

This ensures refunds can also update wallet balance without being blocked.

## How It Works Now

### Order Placement Flow:
1. Customer places order with wallet payment (API call, `webapi_rest` area)
2. `OrderPlaceAfter` observer executes
3. Updates `customer->wallet_balance` attribute to new value (400)
4. Sets registry flag: `wallet_balance_update_in_progress = true`
5. Calls `customerRepository->save($customer)`
6. **CustomerRepositoryPlugin::beforeSave** intercepts:
   - Is `webapi_rest`? YES
   - Is admin? NO
   - Is `wallet_balance_update_in_progress`? **YES!**
   - **Allows update to proceed** ✓
7. **CustomerRepositorySavePlugin::aroundSave** intercepts:
   - Checks `shouldLogTransaction()`
   - Sees `wallet_balance_update_in_progress` flag
   - Returns `false` - **Skips transaction logging** ✓
8. Wallet balance saves successfully (900 → 400)
9. Unregisters the flag
10. Creates transaction record with order reference
11. **Result**: Balance updated + **Single** transaction with order context ✓

### Order Cancellation/Return Flow:
1. Customer cancels or returns order (API call, `webapi_rest` area)
2. `RefundProcessor` calls `WalletRefundService`
3. `WalletRefundService` updates customer wallet balance (400 → 900)
4. Sets registry flag: `wallet_balance_update_in_progress = true`
5. Calls `customerRepository->save($customer)`
6. **CustomerRepositoryPlugin::beforeSave** - checks flag, **allows update** ✓
7. **CustomerRepositorySavePlugin::aroundSave** - sees flag, **skips transaction** ✓
8. Wallet balance saves successfully (400 → 900)
9. Unregisters the flag
10. Creates transaction record with refund/cancel reference and order ID
11. **Result**: Balance updated + **Single** transaction with refund context ✓

### Admin Panel Update Flow:
1. Admin updates customer wallet balance via admin panel (`adminhtml` area)
2. Saves customer
3. `CustomerRepositoryPlugin::beforeSave` checks:
   - Area is `adminhtml`, not `webapi_rest` → **Allows update** ✓
4. `CustomerRepositorySavePlugin::aroundSave` checks:
   - `shouldLogTransaction()` returns `true` (no flag set, `adminhtml` area)
   - **Creates transaction** ✓
5. **Result**: Balance updated + **Single** transaction with admin_panel context ✓

### Admin API Update Flow:
1. Admin updates via API (`webapi_rest` area with admin token)
2. Saves customer (NO flag set because it's via direct customer save, not wallet operation)
3. `CustomerRepositoryPlugin::beforeSave`:
   - Is `webapi_rest`? YES
   - Is admin? **YES** (has admin permissions)
   - **Allows update** ✓
4. `CustomerRepositorySavePlugin::aroundSave`:
   - `shouldLogTransaction()` checks flag - NOT set
   - Area is `webapi_rest` → **Creates transaction** ✓
5. **Result**: Balance updated + **Single** transaction with admin_api context ✓

### Customer Profile Update (BLOCKED):
1. Customer tries to update profile via API
2. Modifies wallet_balance attribute (trying to cheat!)
3. Calls `customerRepository->save($customer)`
4. `CustomerRepositoryPlugin::beforeSave` intercepts:
   - Is `webapi_rest`? YES
   - Is admin? NO
   - Is `wallet_balance_update_in_progress`? NO
   - **BLOCKS update - resets balance to original** ✓
5. **Result**: Wallet balance preserved, customer cannot manipulate it ✓

## Testing

### Test Order Placement:
```bash
# 1. Check initial balance
GET /V1/customers/me/wallet
# Response: 900

# 2. Place order with wallet payment (500)
POST /V1/carts/mine/wallet/apply
POST /V1/carts/mine/wallet/place-order

# 3. Check balance is reduced
GET /V1/customers/me/wallet
# Expected: 400 ✓

# 4. Check transaction history
GET /V1/customers/me/wallet/transactions
# Expected: Single debit entry (900 → 400) with order reference ✓
```

### Test Order Cancellation:
```bash
# 1. Cancel order
POST /V1/orders/{orderId}/cancel

# 2. Check balance is refunded
GET /V1/customers/me/wallet
# Expected: 900 (back to original) ✓

# 3. Check transaction history
# Expected: Credit entry (400 → 900) with cancellation reference ✓
```

### Test Admin Update:
```bash
# 1. Update from admin panel (set to 1500)
# Admin Panel → Customer → Edit → Wallet Balance = 1500 → Save

# 2. Check balance via API
GET /V1/customers/{id}/wallet
# Expected: 1500 ✓

# 3. Check transaction history
# Expected: Credit/Debit entry with admin_panel reference ✓
```

## Files Modified
1. `/src/app/code/Formula/Wallet/Observer/OrderPlaceAfter.php` - Added registry flag
2. `/src/app/code/Formula/Wallet/Plugin/CustomerRepositoryPlugin.php` - **CRITICAL FIX** - Added registry flag check
3. `/src/app/code/Formula/Wallet/Plugin/CustomerRepositorySavePlugin.php` - Early exit for non-admin saves
4. `/src/app/code/Formula/OrderCancellationReturn/Service/WalletRefundService.php` - Added registry flag

## Summary

### Issue #1: Wallet Balance Not Persisting
**Problem:** Balance showed in transaction history but didn't update in database.

**Cause:** `CustomerRepositoryPlugin` was **forcefully resetting** wallet_balance to prevent customer manipulation, but it was also blocking legitimate updates.

**Fix:** Added registry flag `wallet_balance_update_in_progress` that signals legitimate operations, allowing them to bypass the security check.

### Issue #2: Duplicate Transaction Entries
**Problem:** Each order/refund created 2 transactions instead of 1:
- Correct: "Wallet payment for order #123"
- Duplicate: "Admin adjustment - wallet debited via API"

**Cause:** `CustomerRepositorySavePlugin` thought customer API calls (`webapi_rest`) were admin API calls and created duplicate transactions.

**Fix:** Updated `shouldLogTransaction()` to check the registry flag. If flag is set, another service is handling the transaction, so plugin skips logging.

### The Registry Flag Solution
The `wallet_balance_update_in_progress` flag serves two purposes:
1. **Allows balance updates:** Signals to `CustomerRepositoryPlugin` that update is legitimate
2. **Prevents duplicates:** Signals to `CustomerRepositorySavePlugin` to skip transaction logging

**Result:**
- ✓ Wallet balance updates correctly
- ✓ Single transaction per operation
- ✓ Security still prevents customer manipulation
- ✓ Admin updates still work and create transactions
