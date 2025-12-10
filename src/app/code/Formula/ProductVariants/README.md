# Formula Product Variants Module

## Overview

This module automatically groups products with the same name but different ML sizes as variants in the Magento REST API responses. It ensures that product listings return N unique products (not N * variants) with variant information embedded within each product.

## Features

- **Automatic variant detection** based on product SKU parsing (case-insensitive)
- **Smart pagination** - returns correct number of unique products
- **Works with existing products API** - no new endpoints needed
- **Smallest ML first** - displays product with smallest ML size in listings
- **Price-based sorting** - uses lowest variant price for sorting
- **Full variant data** - includes SKU, price, stock, image for each variant
- **Proper stock data** - integrates with StockExtension for accurate salable_qty
- **Single product support** - products with only one ML size also get variants array

## How It Works

### Product SKU Pattern Recognition

The module parses product SKUs to identify variants:

**Format:** `Product Base SKU (XXX ml)`

**Examples:**
- Product Name: `Gentle skin cleanser` / SKU: `Gentle skin cleanser (125 ml)` → Base SKU: "Gentle skin cleanser", ML: "125"
- Product Name: `Gentle skin cleanser` / SKU: `Gentle skin cleanser (250 ml)` → Base SKU: "Gentle skin cleanser", ML: "250"
- Product Name: `Single Product` / SKU: `Single Product (100 ml)` → Base SKU: "Single Product", ML: "100"

Products with the same base SKU (the part before the ML pattern) are grouped as variants.

### API Behavior

#### Product Listing API (`/V1/products?searchCriteria=...`)

**Before this module:**
```json
{
  "items": [
    {"sku": "cleanser-125ml", "name": "Oily skin cleanser (125 ml)", "price": 29.99},
    {"sku": "cleanser-250ml", "name": "Oily skin cleanser (250 ml)", "price": 49.99},
    {"sku": "other-product", "name": "Other Product (100 ml)", "price": 19.99}
  ],
  "total_count": 3
}
```

**After this module:**
```json
{
  "items": [
    {
      "sku": "cleanser-125ml",
      "name": "Oily skin cleanser (125 ml)",
      "price": 29.99,
      "extension_attributes": {
        "variants": [
          {
            "product_id": 123,
            "sku": "cleanser-125ml",
            "name": "Oily skin cleanser (125 ml)",
            "ml_size": "125",
            "price": 29.99,
            "special_price": null,
            "final_price": 29.99,
            "is_in_stock": true,
            "salable_qty": 50.0,
            "image": "https://example.com/media/catalog/product/..."
          },
          {
            "product_id": 124,
            "sku": "cleanser-250ml",
            "name": "Oily skin cleanser (250 ml)",
            "ml_size": "250",
            "price": 49.99,
            "special_price": 44.99,
            "final_price": 44.99,
            "is_in_stock": true,
            "salable_qty": 30.0,
            "image": "https://example.com/media/catalog/product/..."
          }
        ],
        "brand_name": "Formula",
        "category_names": ["Skincare", "Cleansers"],
        "is_in_stock": true,
        "salable_qty": 50.0
      }
    },
    {
      "sku": "other-product",
      "name": "Other Product (100 ml)",
      "price": 19.99,
      "extension_attributes": {
        "variants": [
          {
            "product_id": 125,
            "sku": "other-product",
            "name": "Other Product (100 ml)",
            "ml_size": "100",
            "price": 19.99,
            "special_price": null,
            "final_price": 19.99,
            "is_in_stock": true,
            "salable_qty": 100.0,
            "image": "https://example.com/media/catalog/product/..."
          }
        ]
      }
    }
  ],
  "total_count": 2
}
```

#### Single Product API (`/V1/products/:sku`)

**All products** (whether smallest ML or not) return full variant data:

```json
{
  "sku": "cleanser-250ml",
  "name": "Oily skin cleanser (250 ml)",
  "price": 49.99,
  "extension_attributes": {
    "variants": [
      {
        "product_id": 123,
        "sku": "cleanser-125ml",
        "name": "Oily skin cleanser (125 ml)",
        "ml_size": "125",
        "price": 29.99,
        "final_price": 29.99,
        "is_in_stock": true,
        "salable_qty": 50.0,
        "image": "..."
      },
      {
        "product_id": 124,
        "sku": "cleanser-250ml",
        "name": "Oily skin cleanser (250 ml)",
        "ml_size": "250",
        "price": 49.99,
        "final_price": 44.99,
        "is_in_stock": true,
        "salable_qty": 30.0,
        "image": "..."
      }
    ]
  }
}
```

## Variant Data Structure

Each variant object contains:

| Field | Type | Description |
|-------|------|-------------|
| `product_id` | int | Magento product entity ID |
| `sku` | string | Product SKU |
| `name` | string | Full product name with ML |
| `ml_size` | string\|null | Extracted ML value (e.g., "125") |
| `price` | float | Regular price |
| `special_price` | float\|null | Special price if set |
| `final_price` | float | Final price (special or regular) |
| `is_in_stock` | boolean | Stock status |
| `salable_qty` | float | Available quantity |
| `image` | string\|null | Product image URL |

## Key Features

### 1. Pagination Support

When `pageSize=20` is requested, the API returns **20 unique product groups**, not 20 individual SKUs.

**Example:**
- Database has 40 products (20 groups with 2 variants each)
- Request: `/V1/products?searchCriteria[pageSize]=20`
- Response: 20 products with variants embedded
- `total_count`: 20 (not 40)

### 2. Sorting by Price

Products are sorted by the **lowest variant price** in the group.

**Example:**
- Product A: 125ml ($29.99), 250ml ($49.99) → Sorted at $29.99
- Product B: 100ml ($35.00) → Sorted at $35.00

### 3. Works with All Search Criteria

The module respects all existing search filters:
- Category filters
- Attribute filters
- Search queries
- Custom filters from other modules

### 4. Stock Integration

Integrates with `Formula_StockExtension` module to show accurate stock data per variant.

## Technical Implementation

### Files

```
src/app/code/Formula/ProductVariants/
├── Plugin/
│   └── ProductRepositoryPlugin.php        # Main plugin intercepting product API
├── Helper/
│   └── VariantHelper.php                  # Name parsing & variant fetching logic
├── etc/
│   ├── module.xml                         # Module declaration
│   ├── di.xml                             # Plugin registration (sortOrder: 5)
│   └── extension_attributes.xml           # Declares "variants" attribute
├── registration.php                       # Module registration
└── README.md                              # This file
```

### Plugin Execution Order

**sortOrder: 5** - Runs BEFORE most other Formula plugins (which don't specify sortOrder or use 10+)

This ensures:
1. Variants are added early
2. Other plugins (Brand, Stock, Category, etc.) enhance the data
3. Final response includes all enrichments

### Architecture

**Plugin Pattern:** `ProductRepositoryInterface`

**Intercepted Methods:**
- `afterGet()` - Single product fetch
- `afterGetList()` - Product listings/search

**Data Flow:**

```
1. Client requests products
2. Magento ProductRepository fetches products
3. ProductVariants Plugin (sortOrder: 5):
   a. Groups products by base name
   b. Filters to show only smallest ML per group
   c. Fetches all variants for each group
   d. Adds variants to extension_attributes
   e. Updates total_count
4. Other Formula plugins enhance data (Brand, Stock, etc.)
5. Final response sent to client
```

## Performance Considerations

### Caching

The `VariantHelper` includes internal caching:
- Variant groups cached per base name
- Cache key: `{baseName}_{storeId}`
- Cache cleared between requests

### Database Queries

**Optimization strategy:**
- One query per unique base SKU (not per product)
- Collection filtering at database level (filters on SKU field)
- Minimal product attributes loaded for variants

**Example:**
- 20 products displayed (10 unique base SKUs)
- Database queries: 10 (one per base SKU)

### Tips for Large Catalogs

1. **Indexing:** Ensure product names are indexed
2. **Flat tables:** Consider enabling flat catalog for better performance
3. **Caching:** Use Magento's full-page cache and Varnish
4. **API caching:** Implement API response caching on frontend

## Edge Cases Handled

### 1. Products Without ML Pattern

Products like "Simple Product" (no ML in brackets):
- Base name: "Simple Product"
- ML size: null
- Variants array: `[{ ..., "ml_size": null }]`

### 2. Inconsistent Spacing

Handles variations:
- `Product (125 ml)` ✓
- `Product (125ml)` ✓
- `Product  (125 ml)` ✓ (extra spaces)

### 3. Case Sensitivity

Base name matching is **case-insensitive**:
- "Oily Skin Cleanser (125 ml)"
- "oily skin cleanser (250 ml)"
- Treated as same product group

### 4. Products Across Categories

Variants can exist in different categories:
- Category A: "Cleanser (125 ml)"
- Category B: "Cleanser (250 ml)"
- Still grouped as variants

### 5. Multiple Brackets

Handles complex patterns:
- `Product (Pack of 2) (125 ml)` → Base: "Product (Pack of 2)", ML: "125"
- Captures the **last** `(XXX ml)` pattern

## Frontend Integration

### Display Single Product with Variant Selector

```javascript
// When rendering product
const product = apiResponse.items[0];
const variants = product.extension_attributes.variants;

// Render variant selector
variants.forEach(variant => {
  console.log(`${variant.ml_size} ml - $${variant.final_price}`);
  // 125 ml - $29.99
  // 250 ml - $49.99
});

// Handle variant selection
function selectVariant(sku) {
  // Fetch full product data: GET /V1/products/{sku}
  // Or use variant data directly from variants array
}
```

### Detect Single vs Multiple Variants

```javascript
const variants = product.extension_attributes.variants;

if (variants.length === 1) {
  // Single variant - show price directly
  console.log(`Price: $${variants[0].final_price}`);
} else {
  // Multiple variants - show selector
  console.log('Choose size:');
  variants.forEach(v => console.log(`${v.ml_size} ml`));
}
```

### Stock Display Per Variant

```javascript
variants.forEach(variant => {
  if (variant.is_in_stock && variant.salable_qty > 0) {
    console.log(`${variant.ml_size} ml - In Stock (${variant.salable_qty} available)`);
  } else {
    console.log(`${variant.ml_size} ml - Out of Stock`);
  }
});
```

## Testing

### Manual Testing

#### Test Product Listing
```bash
curl "http://localhost:8080/rest/V1/products?searchCriteria[pageSize]=10" \
  -H "Authorization: Bearer {token}"
```

**Verify:**
- ✓ Products with same base name appear once
- ✓ `variants` array exists in `extension_attributes`
- ✓ Smallest ML variant shown in main results
- ✓ `total_count` reflects unique products

#### Test Single Product
```bash
curl "http://localhost:8080/rest/V1/products/{sku}" \
  -H "Authorization: Bearer {token}"
```

**Verify:**
- ✓ `variants` array exists
- ✓ All variants included regardless of which SKU fetched
- ✓ Variants sorted by ML size (smallest first)

#### Test Search & Filters
```bash
curl "http://localhost:8080/rest/V1/products?searchCriteria[filter_groups][0][filters][0][field]=category_id&searchCriteria[filter_groups][0][filters][0][value]=5" \
  -H "Authorization: Bearer {token}"
```

**Verify:**
- ✓ Filtering still works correctly
- ✓ Variant grouping applied to filtered results

### Automated Testing

See `Test/Integration/VariantGroupingTest.php` (to be created) for integration tests.

## Troubleshooting

### Issue: Variants not appearing

**Check:**
1. Module enabled: `bin/magento module:status Formula_ProductVariants`
2. Cache cleared: `bin/magento cache:flush`
3. Product SKUs follow pattern: `SKU (XXX ml)`

### Issue: Wrong product shown in listing

**Check:**
1. ML sizes correctly parsed
2. Smallest ML variant has stock
3. Check logs: `var/log/system.log`

### Issue: Performance slow

**Solutions:**
1. Enable Magento caching
2. Verify database indexes
3. Check query logs for N+1 problems
4. Consider implementing Redis caching for variant groups

### Debugging

Enable debug logging:

```php
// In VariantHelper.php, add:
$this->logger->debug('Parsed SKU', [
    'sku' => $sku,
    'base_sku' => $parsed['base_sku'],
    'ml_size' => $parsed['ml_size']
]);
```

Check logs: `docker-compose exec php tail -f var/log/system.log`

## Module Dependencies

- `Magento_Catalog` (required)
- `Formula_StockExtension` (optional - enhances stock data)

## Compatibility

- **Magento Version:** 2.4.7-p3
- **PHP Version:** 7.4+
- **Compatible with:** All Formula custom modules

## Future Enhancements

Potential improvements:
1. Admin UI to manually group products as variants
2. Support for other units (oz, g, kg, etc.)
3. Variant attribute customization via admin config
4. GraphQL API support
5. Variant swatch images
6. Configurable regex patterns via admin

## Support

For issues or questions:
1. Check logs: `var/log/system.log`
2. Verify module enabled and cache cleared
3. Test with sample products following naming convention

## License

Proprietary - Formula
