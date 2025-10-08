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

- Plugin now **exits early** (line 61-62) for non-admin saves
- This prevents database re-fetch and cache conflicts
- Plugin ONLY creates transactions for admin area saves

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
1. Customer places order with wallet payment
2. `OrderPlaceAfter` observer executes
3. Updates `customer->wallet_balance` attribute to new value (400)
4. Sets registry flag: `wallet_balance_update_in_progress = true`
5. Calls `customerRepository->save($customer)`
6. `CustomerRepositoryPlugin::beforeSave` intercepts:
   - Checks: Is `webapi_rest`? YES
   - Checks: Is admin? NO
   - Checks: Is `wallet_balance_update_in_progress` flag set? **YES!**
   - **Allows update to proceed** ✓
7. Wallet balance saves successfully (900 → 400)
8. Unregisters the flag
9. Creates transaction record with order reference
10. **Result**: Balance updated + Single transaction with order context ✓

### Order Cancellation/Return Flow:
1. Customer cancels or returns order
2. `RefundProcessor` calls `WalletRefundService`
3. `WalletRefundService` updates customer wallet balance (400 → 900)
4. Sets registry flag: `wallet_balance_update_in_progress = true`
5. Calls `customerRepository->save($customer)`
6. `CustomerRepositoryPlugin::beforeSave` intercepts:
   - Checks flag, **allows update** ✓
7. Wallet balance saves successfully (400 → 900)
8. Unregisters the flag
9. Creates transaction record with refund/cancel reference and order ID
10. **Result**: Balance updated + Single transaction with refund context ✓

### Admin Panel Update Flow:
1. Admin updates customer wallet balance via admin panel
2. Saves customer
3. `CustomerRepositoryPlugin::beforeSave` checks:
   - Area is `adminhtml`, not `webapi_rest` → **Allows update** ✓
4. `CustomerRepositorySavePlugin::aroundSave` checks:
   - Area is `adminhtml` → **Creates transaction** ✓
5. **Result**: Balance updated + Single transaction with admin context ✓

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

The wallet balance wasn't updating because `CustomerRepositoryPlugin` was **forcefully resetting** the balance back to the original value to prevent customer manipulation.

The fix uses a **registry flag** (`wallet_balance_update_in_progress`) to signal legitimate wallet operations, allowing them to bypass the security check while still protecting against customer manipulation.
