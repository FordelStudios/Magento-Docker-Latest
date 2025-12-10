# Quick Test Guide - Stock Data Fix

## Test Command (Browser)

1. Add these headers in your browser REST client:
   ```
   Accept: application/json
   Authorization: Bearer YOUR_TOKEN
   ```

2. Visit this URL:
   ```
   http://localhost:8080/rest/V1/products?searchCriteria[pageSize]=3
   ```

## What You Should See NOW (Fixed)

Look for a product with variants. The response should look like this:

```json
{
  "items": [
    {
      "id": 123,
      "sku": "Gentle skin cleanser (125 ml)",
      "name": "Gentle skin cleanser",
      "price": 29.99,
      "extension_attributes": {
        "salable_qty": 1,              ← Main product stock
        "is_in_stock": true,
        "variants": [
          {
            "product_id": 123,
            "sku": "Gentle skin cleanser (125 ml)",
            "name": "Gentle skin cleanser",
            "ml_size": "125",
            "price": 29.99,
            "final_price": 29.99,
            "is_in_stock": true,
            "salable_qty": 1.0,        ← ✅ SHOULD BE NON-ZERO NOW!
            "image": "https://..."
          },
          {
            "product_id": 124,
            "sku": "Gentle skin cleanser (250 ml)",
            "name": "Gentle skin cleanser",
            "ml_size": "250",
            "price": 49.99,
            "final_price": 49.99,
            "is_in_stock": true,
            "salable_qty": 5.0,        ← ✅ SHOULD BE NON-ZERO NOW!
            "image": "https://..."
          }
        ]
      }
    }
  ],
  "total_count": 1
}
```

## What Was BROKEN Before

Previously you would see:
```json
"variants": [
  {
    "salable_qty": 0,  ← ❌ WRONG! Always zero
    "is_in_stock": true
  }
]
```

## Quick Checklist

✅ Check `extension_attributes.variants` array exists
✅ Check each variant has `salable_qty` field
✅ Verify `salable_qty` values are NOT all zeros
✅ Verify in-stock products show `salable_qty > 0`
✅ Verify out-of-stock products show `salable_qty: 0` or `is_in_stock: false`

## Stock Determination Formula

```javascript
function isAvailable(variant) {
  return variant.is_in_stock === true && variant.salable_qty > 0;
}
```

## If Still Broken

Run these commands:
```bash
docker-compose exec php php bin/magento cache:flush
docker-compose exec php php bin/magento setup:di:compile
```

Then test again!

---

**Quick Comparison:**

| Field | BEFORE (Broken) | AFTER (Fixed) |
|-------|----------------|---------------|
| Main product `salable_qty` | ✅ Works (e.g., 1) | ✅ Works (e.g., 1) |
| Variant 1 `salable_qty` | ❌ Always 0 | ✅ Correct (e.g., 1) |
| Variant 2 `salable_qty` | ❌ Always 0 | ✅ Correct (e.g., 5) |

---

**Status**: Fix applied, ready for testing!
