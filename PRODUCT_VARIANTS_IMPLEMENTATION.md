# Product Variants Implementation - SKU-Based Grouping

## Overview

The `Formula_ProductVariants` module has been implemented to automatically group products with the same base SKU but different ML sizes as variants in the Magento REST API.

## Key Change: SKU-Based (Not Name-Based)

**IMPORTANT:** The module uses **product SKU** (not product name) to identify and group variants.

### Your Product Structure:
```
Product Name: Gentle skin cleanser
Product SKU:  Gentle skin cleanser (125 ml)

Product Name: Gentle skin cleanser
Product SKU:  Gentle skin cleanser (250 ml)
```

Both products share the base SKU "Gentle skin cleanser" and will be grouped as variants.

## How It Works

### SKU Pattern Recognition

The module parses SKUs using this pattern: `Base SKU (XXX ml)`

**Parsing Examples:**
- `Gentle skin cleanser (125 ml)` → Base: `Gentle skin cleanser`, ML: `125`
- `Gentle skin cleanser (250 ml)` → Base: `Gentle skin cleanser`, ML: `250`
- `Product (100ml)` → Base: `Product`, ML: `100` (space optional)
- `Single Product` → Base: `Single Product`, ML: `null` (no ML pattern)

### API Response Structure

**Before:**
```json
{
  "items": [
    {"sku": "Gentle skin cleanser (125 ml)", "name": "Gentle skin cleanser", "price": 29.99},
    {"sku": "Gentle skin cleanser (250 ml)", "name": "Gentle skin cleanser", "price": 49.99}
  ],
  "total_count": 2
}
```

**After (with variants):**
```json
{
  "items": [
    {
      "sku": "Gentle skin cleanser (125 ml)",
      "name": "Gentle skin cleanser",
      "price": 29.99,
      "extension_attributes": {
        "variants": [
          {
            "product_id": 123,
            "sku": "Gentle skin cleanser (125 ml)",
            "name": "Gentle skin cleanser",
            "ml_size": "125",
            "price": 29.99,
            "special_price": null,
            "final_price": 29.99,
            "is_in_stock": true,
            "salable_qty": 50.0,
            "image": "https://..."
          },
          {
            "product_id": 124,
            "sku": "Gentle skin cleanser (250 ml)",
            "name": "Gentle skin cleanser",
            "ml_size": "250",
            "price": 49.99,
            "special_price": 44.99,
            "final_price": 44.99,
            "is_in_stock": true,
            "salable_qty": 30.0,
            "image": "https://..."
          }
        ],
        "brand_name": "Formula",
        "category_names": ["Skincare"]
      }
    }
  ],
  "total_count": 1
}
```

## Key Features

1. ✅ **SKU-based grouping** - Uses product SKU field (not name) to identify variants
2. ✅ **Correct pagination** - Returns N unique products, not N * variants
3. ✅ **Smallest ML first** - Shows product with smallest ML size in listings
4. ✅ **All SKUs accessible** - Any variant SKU can be fetched via API
5. ✅ **Price sorting** - Uses lowest variant price for sorting
6. ✅ **ML size extraction** - Extracted from SKU and included in variant data

## Testing the Implementation

### Method 1: Direct API Call

```bash
# 1. Get authentication token
TOKEN=$(curl -s -X POST "http://localhost:8080/rest/V1/integration/admin/token" \
  -H "Content-Type: application/json" \
  -d '{"username":"YOUR_ADMIN_USERNAME","password":"YOUR_ADMIN_PASSWORD"}' | tr -d '"')

# 2. Test product listing
curl -X GET "http://localhost:8080/rest/V1/products?searchCriteria[pageSize]=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq

# 3. Test single product (replace with your actual SKU)
curl -X GET "http://localhost:8080/rest/V1/products/YOUR-PRODUCT-SKU" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq
```

### Method 2: Browser + REST Client Extension

1. Install a browser REST client (e.g., "ModHeader" for Chrome)
2. Get token from step 1 above
3. Add header: `Authorization: Bearer YOUR_TOKEN`
4. Visit: `http://localhost:8080/rest/V1/products?searchCriteria[pageSize]=5`

### What to Verify:

✓ Each product has `extension_attributes.variants` array
✓ Products with same base SKU appear once (not multiple times)
✓ `total_count` reflects unique products (not total SKUs)
✓ Variants array contains all ML sizes for that product
✓ Each variant has: `sku`, `ml_size`, `price`, `final_price`, `is_in_stock`, `salable_qty`
✓ Variants are sorted by ML size (smallest first)

## Frontend Team Instructions

### API Endpoints (No changes)

**Product Listing:**
```
GET /rest/V1/products?searchCriteria[pageSize]=20
```

**Single Product:**
```
GET /rest/V1/products/{sku}
```

### New Response Field

All products now include `extension_attributes.variants`:

```javascript
{
  "sku": "Product SKU (125 ml)",
  "name": "Product Name",
  "price": 29.99,
  "extension_attributes": {
    "variants": [
      {
        "product_id": 123,
        "sku": "Product SKU (125 ml)",
        "name": "Product Name",
        "ml_size": "125",          // ← Extracted from SKU
        "price": 29.99,
        "special_price": null,
        "final_price": 29.99,
        "is_in_stock": true,
        "salable_qty": 50.0,
        "image": "https://..."
      },
      // ... more variants
    ],
    "brand_name": "Formula",
    "category_names": ["Skincare"],
    // ... other extension attributes
  }
}
```

### Frontend Implementation Example

```javascript
// Check if product has multiple variants
const product = response.items[0];
const variants = product.extension_attributes?.variants || [];

if (variants.length > 1) {
  // Show variant selector
  console.log('Available sizes:');
  variants.forEach(variant => {
    console.log(`${variant.ml_size} ml - $${variant.final_price}`);
    if (!variant.is_in_stock) {
      console.log('  (Out of stock)');
    }
  });
} else if (variants.length === 1) {
  // Single variant - show price directly
  console.log(`Price: $${variants[0].final_price}`);
}

// When user selects a variant, use the variant's SKU
function selectVariant(selectedSku) {
  // Option 1: Use variant data from variants array (no API call)
  const selectedVariant = variants.find(v => v.sku === selectedSku);
  updateUI(selectedVariant);

  // Option 2: Fetch full product data (if needed)
  fetch(`/rest/V1/products/${encodeURIComponent(selectedSku)}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
}
```

## Module Files

```
src/app/code/Formula/ProductVariants/
├── Plugin/
│   └── ProductRepositoryPlugin.php        # Main plugin (SKU-based grouping)
├── Helper/
│   └── VariantHelper.php                  # SKU parsing & variant fetching
├── etc/
│   ├── module.xml
│   ├── di.xml
│   └── extension_attributes.xml
├── registration.php
└── README.md
```

## Module Status

✅ **Installed and Enabled**
✅ **Cache Cleared**
✅ **Stock Data Fix Applied** (2025-12-10)
✅ **Ready for Testing**

## Recent Fix: Stock Data (2025-12-10)

**Issue**: All variants were showing `salable_qty: 0` even when products had stock.

**Root Cause**: The `getVariantsByBaseSku()` method used direct collection queries which bypassed the `StockExtension` plugin.

**Solution**: Modified VariantHelper to use `ProductRepository->getList()` with SearchCriteria, ensuring proper plugin execution and stock data enrichment.

**Result**: Variants now show correct `salable_qty` and `is_in_stock` values from the StockExtension module.

See `STOCK_DATA_FIX_VERIFICATION.md` for detailed verification steps.

## Important Notes

1. **SKU Format Required:** Products must have SKUs in format: `Base SKU (XXX ml)`
2. **Case Insensitive:** Base SKU matching is case-insensitive
3. **No Manual Configuration:** Works automatically based on SKU pattern
4. **Backward Compatible:** All existing API endpoints work unchanged
5. **Integrates with Other Modules:** Works alongside your 31 other Formula modules (including StockExtension)
6. **Stock Data:** Uses `is_in_stock && salable_qty > 0` to determine availability

## Troubleshooting

### If variants don't appear:

```bash
# 1. Check module is enabled
docker-compose exec php php bin/magento module:status Formula_ProductVariants

# 2. Clear cache
docker-compose exec php php bin/magento cache:flush

# 3. Check product SKUs match pattern
# SKU should be: "Product Name (125 ml)"
```

### Check logs:

```bash
docker-compose exec php tail -f var/log/system.log
```

## Next Steps

1. **Test with actual products** using the API calls above
2. **Share API documentation** with frontend team (see "Frontend Team Instructions" section)
3. **Update frontend** to display variant selector using `extension_attributes.variants`
4. **Monitor performance** - check API response times with production data

## Support

- Full documentation: `src/app/code/Formula/ProductVariants/README.md`
- Test script: `test-variants-api.sh`
- Module code: `src/app/code/Formula/ProductVariants/`

---

**Implementation completed on:** 2025-12-10
**Module version:** 1.0.0
**Magento version:** 2.4.7-p3
