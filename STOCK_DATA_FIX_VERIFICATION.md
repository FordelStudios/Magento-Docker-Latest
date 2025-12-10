# Stock Data Fix Verification Guide

## What Was Fixed

**Problem**: All variants were showing `salable_qty: 0` and appearing out of stock, even though the main product showed correct stock levels.

**Root Cause**: The `getVariantsByBaseSku()` method in VariantHelper was using direct collection queries (`CollectionFactory->create()`) which bypassed the `StockExtension` plugin. This meant variants didn't get their stock data enriched.

**Solution**: Modified `VariantHelper.php` to use `ProductRepository->getList()` with SearchCriteria, ensuring all plugins (including StockExtension) properly enrich the variant data.

## Files Modified

- `/src/app/code/Formula/ProductVariants/Helper/VariantHelper.php`
  - Added dependencies: `ProductRepositoryInterface`, `SearchCriteriaBuilder`, `FilterBuilder`
  - Rewrote `getVariantsByBaseSku()` to use repository instead of collections
  - Lines modified: ~90-135

## Verification Steps

### 1. Test the API

Run this command to test (replace with your actual credentials if needed):

```bash
# Get token
TOKEN=$(curl -s -X POST "http://localhost:8080/rest/V1/integration/admin/token" \
  -H "Content-Type: application/json" \
  -d '{"username":"YOUR_USERNAME","password":"YOUR_PASSWORD"}' | tr -d '"')

# Test products API with JSON output
curl -H "Authorization: Bearer $TOKEN" \
     -H "Accept: application/json" \
     'http://localhost:8080/rest/V1/products?searchCriteria[pageSize]=3' | jq
```

Or in your browser with a REST client:
1. Add header: `Accept: application/json`
2. Add header: `Authorization: Bearer YOUR_TOKEN`
3. Visit: `http://localhost:8080/rest/V1/products?searchCriteria[pageSize]=3`

### 2. What to Check

Look for products that have variants. Each product should have:

```json
{
  "sku": "Product SKU (125 ml)",
  "name": "Product Name",
  "extension_attributes": {
    "salable_qty": 1,           // ← Main product stock (should be non-zero if in stock)
    "is_in_stock": true,
    "variants": [
      {
        "product_id": 123,
        "sku": "Product SKU (125 ml)",
        "ml_size": "125",
        "price": 29.99,
        "final_price": 29.99,
        "is_in_stock": true,
        "salable_qty": 1.0,      // ← Variant stock (SHOULD NOW SHOW CORRECT VALUE!)
        "image": "https://..."
      },
      {
        "product_id": 124,
        "sku": "Product SKU (250 ml)",
        "ml_size": "250",
        "price": 49.99,
        "final_price": 49.99,
        "is_in_stock": true,
        "salable_qty": 5.0,      // ← Variant stock (SHOULD NOW SHOW CORRECT VALUE!)
        "image": "https://..."
      }
    ]
  }
}
```

### 3. Expected Results

✅ **BEFORE THE FIX (Broken):**
- Main product: `salable_qty: 1`, `is_in_stock: true`
- Variant 1: `salable_qty: 0`, `is_in_stock: true` ❌ (WRONG!)
- Variant 2: `salable_qty: 0`, `is_in_stock: true` ❌ (WRONG!)

✅ **AFTER THE FIX (Correct):**
- Main product: `salable_qty: 1`, `is_in_stock: true`
- Variant 1: `salable_qty: 1`, `is_in_stock: true` ✅ (CORRECT!)
- Variant 2: `salable_qty: 5`, `is_in_stock: true` ✅ (CORRECT!)

### 4. Stock Determination Logic

Use this formula to determine if a product is available:

```javascript
function isProductAvailable(variant) {
  return variant.is_in_stock === true && variant.salable_qty > 0;
}
```

**Example Usage:**
```javascript
const variants = product.extension_attributes.variants || [];

// Filter available variants
const availableVariants = variants.filter(v =>
  v.is_in_stock === true && v.salable_qty > 0
);

if (availableVariants.length === 0) {
  console.log('Product out of stock');
} else {
  console.log(`Available in ${availableVariants.length} size(s)`);
}
```

## Technical Details

### What Changed in the Code

**Before (Broken):**
```php
// Direct collection query - bypasses plugins
$collection = $this->productCollectionFactory->create();
$collection->addFieldToFilter('sku', ['like' => $baseSku . ' (%ml)%']);
$products = $collection->getItems();
```

**After (Fixed):**
```php
// Use ProductRepository with SearchCriteria - triggers all plugins
$skuFilter = $this->filterBuilder
    ->setField('sku')
    ->setValue($baseSku . ' (%ml)%')
    ->setConditionType('like')
    ->create();

$searchCriteria = $this->searchCriteriaBuilder
    ->addFilters([$skuFilter])
    ->create();

$searchResults = $this->productRepository->getList($searchCriteria);
$products = $searchResults->getItems();
```

### Why This Matters

- **ProductRepository**: Ensures proper plugin execution chain
- **Plugin Chain**: StockExtension plugin runs and enriches products with `salable_qty` and `is_in_stock`
- **sortOrder 1000**: Our ProductVariants plugin runs after stock enrichment is complete

## Troubleshooting

If stock data still shows as zero:

1. **Check module is enabled:**
   ```bash
   docker-compose exec php php bin/magento module:status Formula_ProductVariants
   ```

2. **Clear all caches:**
   ```bash
   docker-compose exec php php bin/magento cache:flush
   docker-compose exec php php bin/magento cache:clean
   ```

3. **Verify StockExtension is enabled:**
   ```bash
   docker-compose exec php php bin/magento module:status Formula_StockExtension
   ```

4. **Check logs for errors:**
   ```bash
   docker-compose exec php tail -f var/log/system.log
   ```

5. **Verify products have actual stock in Magento admin:**
   - Go to Catalog → Products
   - Edit a product
   - Check "Advanced Inventory" settings
   - Ensure "Qty" is greater than 0

## What to Report Back

Please verify and confirm:

1. ✅ Do variants now show correct `salable_qty` values (not zero)?
2. ✅ Does the formula `is_in_stock === true && salable_qty > 0` correctly identify available products?
3. ✅ Do out-of-stock products show `salable_qty: 0` or `is_in_stock: false`?
4. ✅ Does the API response time seem acceptable?

If everything works correctly, the stock data fix is complete! 🎉

## Next Steps

Once verified:
- Share this updated API behavior with your frontend team
- Update frontend code to use the stock determination formula
- Test add-to-cart flow with in-stock and out-of-stock variants

---

**Fix implemented**: 2025-12-10
**Files modified**: VariantHelper.php (getVariantsByBaseSku method)
**Status**: Ready for verification
