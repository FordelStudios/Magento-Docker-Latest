# Product Variants API - Frontend Integration Guide

## Overview

The Magento Products API now automatically groups products with different ML sizes as variants. Products that share the same base SKU (ignoring case and ML size) are returned as a single product with a `variants` array.

---

## What Changed

### Before (Old Behavior)
```json
{
  "items": [
    {"sku": "Gentle Skin Cleanser (125 ml)", "name": "Gentle Skin Cleanser", "price": 429},
    {"sku": "Gentle Skin Cleanser (250 ml)", "name": "Gentle Skin Cleanser", "price": 769},
    {"sku": "Oily skin cleanser (125 ml)", "name": "Oily skin cleanser", "price": 699}
  ],
  "total_count": 3
}
```

### After (New Behavior)
```json
{
  "items": [
    {
      "sku": "Gentle Skin Cleanser (125 ml)",
      "name": "Gentle Skin Cleanser",
      "price": 429,
      "extension_attributes": {
        "variants": [
          {
            "product_id": 4,
            "sku": "Gentle Skin Cleanser (125 ml)",
            "name": "Gentle Skin Cleanser",
            "ml_size": "125",
            "price": 429.00,
            "special_price": 403.26,
            "final_price": 403.26,
            "is_in_stock": true,
            "salable_qty": 1.0,
            "image": "http://localhost:8080/media/catalog/product/..."
          },
          {
            "product_id": 3,
            "sku": "Gentle Skin Cleanser (250 ml)",
            "name": "Gentle Skin Cleanser",
            "ml_size": "250",
            "price": 769.00,
            "special_price": 722.86,
            "final_price": 722.86,
            "is_in_stock": true,
            "salable_qty": 0.0,
            "image": "http://localhost:8080/media/catalog/product/..."
          }
        ]
      }
    },
    {
      "sku": "Oily skin cleanser (125 ml)",
      "name": "Oily skin cleanser",
      "price": 699,
      "extension_attributes": {
        "variants": [
          {
            "product_id": 5,
            "sku": "Oily skin cleanser (125 ml)",
            "name": "Oily skin cleanser (125 ml)",
            "ml_size": "125",
            "price": 699.00,
            "special_price": 657.06,
            "final_price": 657.06,
            "is_in_stock": true,
            "salable_qty": 8.0,
            "image": "http://localhost:8080/media/catalog/product/..."
          }
        ]
      }
    }
  ],
  "total_count": 2
}
```

**Key Changes:**
- ✅ Only **2 products** returned instead of 3
- ✅ First product has **2 variants** (125ml and 250ml)
- ✅ Second product has **1 variant** (125ml only)
- ✅ `total_count` now reflects **unique products**, not total SKUs

---

## API Endpoints

### 1. Product Listing API

**Endpoint:** `GET /rest/V1/products`

**Query Parameters:**
```
searchCriteria[pageSize]=20
searchCriteria[currentPage]=1
searchCriteria[filter_groups][0][filters][0][field]=category_id
searchCriteria[filter_groups][0][filters][0][value]=4
```

**Response Structure:**
```json
{
  "items": [
    {
      "id": 3,
      "sku": "Product SKU (125 ml)",
      "name": "Product Name",
      "price": 429.00,
      "extension_attributes": {
        "brand_name": "Cetaphil",
        "category_names": ["Face care", "Face Wash"],
        "is_in_stock": true,
        "salable_qty": 10.0,
        "variants": [
          // Array of variant objects (see Variant Object Structure below)
        ]
      }
    }
  ],
  "search_criteria": { /* ... */ },
  "total_count": 20
}
```

### 2. Single Product API

**Endpoint:** `GET /rest/V1/products/{sku}`

**Example:** `GET /rest/V1/products/Gentle%20Skin%20Cleanser%20(125%20ml)`

**Response:** Same structure as product listing, but returns a single product object with `variants` array.

**Important:** You can fetch **any variant SKU** (125ml, 250ml, etc.) and it will return the full variant information for all sizes.

---

## Variant Object Structure

Each object in the `variants` array contains:

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `product_id` | integer | Magento product entity ID | `4` |
| `sku` | string | Product SKU | `"Gentle Skin Cleanser (125 ml)"` |
| `name` | string | Product name | `"Gentle Skin Cleanser"` |
| `ml_size` | string\|null | Extracted ML value | `"125"` or `null` |
| `price` | float | Regular price | `429.00` |
| `special_price` | float\|null | Special/sale price | `403.26` or `null` |
| `final_price` | float | Final price (special or regular) | `403.26` |
| `is_in_stock` | boolean | Stock availability | `true` or `false` |
| `salable_qty` | float | Available quantity | `10.0` |
| `image` | string\|null | Product image URL | `"http://..."` or `null` |

---

## Frontend Implementation Guide

### Step 1: Check if Product Has Multiple Variants

```javascript
function hasMultipleVariants(product) {
  const variants = product.extension_attributes?.variants || [];
  return variants.length >= 2;
}

// Example usage
if (hasMultipleVariants(product)) {
  // Show variant selector (bottom sheet)
  showVariantSelector(product.extension_attributes.variants);
} else {
  // Show single product (no selector needed)
  showSingleProduct(product);
}
```

### Step 2: Display Variant Selector (Bottom Sheet)

When a product has **2 or more variants**, display a bottom sheet/modal for size selection:

```javascript
function showVariantSelector(variants) {
  // Sort variants by ML size (should already be sorted, but for safety)
  const sortedVariants = [...variants].sort((a, b) => {
    const mlA = parseInt(a.ml_size) || 0;
    const mlB = parseInt(b.ml_size) || 0;
    return mlA - mlB;
  });

  // Display bottom sheet with variant options
  sortedVariants.forEach(variant => {
    const option = {
      label: `${variant.ml_size} ml`,
      price: variant.final_price,
      originalPrice: variant.price,
      hasDiscount: variant.special_price !== null,
      inStock: variant.is_in_stock,
      quantity: variant.salable_qty,
      sku: variant.sku,
      image: variant.image
    };

    // Render option in bottom sheet
    renderVariantOption(option);
  });
}
```

### Step 3: Handle Variant Selection

When user selects a variant:

```javascript
function onVariantSelected(selectedVariant) {
  // Update product display
  updateProductPrice(selectedVariant.final_price);
  updateProductImage(selectedVariant.image);
  updateProductSku(selectedVariant.sku);

  // Update stock status
  if (!selectedVariant.is_in_stock || selectedVariant.salable_qty <= 0) {
    showOutOfStock();
  } else {
    showAddToCartButton();
  }

  // When adding to cart, use the selected variant's SKU
  addToCart(selectedVariant.sku, quantity);
}
```

### Step 4: Display Pricing

Show pricing with discount indication:

```javascript
function renderPrice(variant) {
  if (variant.special_price && variant.special_price < variant.price) {
    return `
      <div class="price">
        <span class="special-price">₹${variant.final_price}</span>
        <span class="original-price strikethrough">₹${variant.price}</span>
        <span class="discount">${calculateDiscount(variant)}% OFF</span>
      </div>
    `;
  } else {
    return `<div class="price">₹${variant.final_price}</div>`;
  }
}

function calculateDiscount(variant) {
  if (!variant.special_price) return 0;
  const discount = ((variant.price - variant.special_price) / variant.price) * 100;
  return Math.round(discount);
}
```

### Step 5: Stock Status Display

```javascript
function renderStockStatus(variant) {
  if (!variant.is_in_stock || variant.salable_qty <= 0) {
    return '<span class="out-of-stock">Out of Stock</span>';
  } else if (variant.salable_qty <= 5) {
    return `<span class="low-stock">Only ${variant.salable_qty} left!</span>`;
  } else {
    return '<span class="in-stock">In Stock</span>';
  }
}
```

---

## Complete Example: Product Card Component

```javascript
// React/Vue/Angular example (adapt to your framework)

function ProductCard({ product }) {
  const variants = product.extension_attributes?.variants || [];
  const hasMultipleVariants = variants.length >= 2;
  const [selectedVariant, setSelectedVariant] = useState(variants[0]);
  const [showVariantSheet, setShowVariantSheet] = useState(false);

  return (
    <div className="product-card">
      {/* Product Image */}
      <img src={selectedVariant.image} alt={product.name} />

      {/* Product Name */}
      <h3>{product.name}</h3>

      {/* Price */}
      <div className="price">
        {selectedVariant.special_price ? (
          <>
            <span className="special-price">₹{selectedVariant.final_price}</span>
            <span className="original-price">₹{selectedVariant.price}</span>
          </>
        ) : (
          <span>₹{selectedVariant.price}</span>
        )}
      </div>

      {/* Variant Selector - Only show if multiple variants */}
      {hasMultipleVariants ? (
        <>
          {/* Current Selection Display */}
          <button
            className="variant-selector-trigger"
            onClick={() => setShowVariantSheet(true)}
          >
            {selectedVariant.ml_size} ml
            <span className="dropdown-icon">▼</span>
          </button>

          {/* Bottom Sheet Modal */}
          {showVariantSheet && (
            <BottomSheet onClose={() => setShowVariantSheet(false)}>
              <h4>Select Size</h4>
              {variants.map(variant => (
                <div
                  key={variant.sku}
                  className={`variant-option ${selectedVariant.sku === variant.sku ? 'selected' : ''}`}
                  onClick={() => {
                    setSelectedVariant(variant);
                    setShowVariantSheet(false);
                  }}
                >
                  <div className="size">{variant.ml_size} ml</div>
                  <div className="price">₹{variant.final_price}</div>
                  <div className="stock">
                    {variant.is_in_stock && variant.salable_qty > 0
                      ? `In Stock (${variant.salable_qty})`
                      : 'Out of Stock'
                    }
                  </div>
                </div>
              ))}
            </BottomSheet>
          )}
        </>
      ) : (
        // Single variant - show ML size without selector
        <div className="single-variant">
          {selectedVariant.ml_size && `${selectedVariant.ml_size} ml`}
        </div>
      )}

      {/* Stock Status */}
      <div className={`stock-status ${selectedVariant.is_in_stock ? 'in-stock' : 'out-of-stock'}`}>
        {selectedVariant.is_in_stock && selectedVariant.salable_qty > 0
          ? 'In Stock'
          : 'Out of Stock'
        }
      </div>

      {/* Add to Cart Button */}
      <button
        className="add-to-cart"
        disabled={!selectedVariant.is_in_stock || selectedVariant.salable_qty <= 0}
        onClick={() => addToCart(selectedVariant.sku, 1)}
      >
        Add to Cart
      </button>
    </div>
  );
}
```

---

## UI/UX Recommendations

### 1. **Product Card Display**
- **Single Variant:** Show ML size as static text (e.g., "125 ml")
- **Multiple Variants:** Show ML size with dropdown icon (e.g., "125 ml ▼")

### 2. **Bottom Sheet Content**
Display each variant option with:
- ✅ ML Size (large, bold text)
- ✅ Price (with discount if applicable)
- ✅ Stock status badge (In Stock / Out of Stock / Low Stock)
- ✅ Product image (optional but recommended)
- ✅ Visual indicator for currently selected variant

### 3. **Default Selection**
- Always select the **first variant** (smallest ML) by default
- The API returns variants sorted by ML size (smallest first)

### 4. **Stock Handling**
- **Out of stock variants:** Show but disable selection
- **Low stock variants:** Show warning (e.g., "Only 2 left!")
- **In stock variants:** Enable selection normally

### 5. **Price Display**
- If `special_price` exists and is less than `price`: Show both (crossed-out regular, highlighted special)
- Use `final_price` for cart operations (it's already calculated)

---

## Edge Cases to Handle

### 1. Product with No Variants Array
```javascript
// Safety check
const variants = product.extension_attributes?.variants || [];

if (variants.length === 0) {
  // Fallback: treat product as single item
  // This shouldn't happen, but good to handle
  console.warn('Product missing variants array:', product.sku);
}
```

### 2. Product with ml_size = null
```javascript
// Some products might not have ML pattern in SKU
function displaySize(variant) {
  if (variant.ml_size) {
    return `${variant.ml_size} ml`;
  } else {
    return 'Standard Size'; // Or hide size display
  }
}
```

### 3. All Variants Out of Stock
```javascript
const allOutOfStock = variants.every(v => !v.is_in_stock || v.salable_qty <= 0);

if (allOutOfStock) {
  showNotifyMeButton(); // Instead of Add to Cart
}
```

### 4. Variant Images
```javascript
// Variant might have null image
function getVariantImage(variant, fallbackImage) {
  return variant.image || fallbackImage || '/default-product-image.jpg';
}
```

---

## Add to Cart Integration

### Using Selected Variant SKU

```javascript
// Always use the selected variant's SKU when adding to cart
function addToCart(selectedVariant, quantity = 1) {
  const payload = {
    cartItem: {
      sku: selectedVariant.sku,  // ← Use variant SKU, not parent product SKU
      qty: quantity,
      quote_id: cartId
    }
  };

  return fetch('/rest/V1/carts/mine/items', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${customerToken}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });
}
```

---

## Testing Checklist

### Frontend Team Should Test:

- [ ] Product with 2+ variants shows variant selector
- [ ] Product with 1 variant shows size but no selector
- [ ] Bottom sheet displays all variants correctly
- [ ] Selected variant updates price and image
- [ ] Out of stock variants are disabled/grayed out
- [ ] Discount prices display correctly
- [ ] Add to cart uses correct variant SKU
- [ ] Pagination works (total_count reflects unique products)
- [ ] Single product page loads correctly for any variant SKU
- [ ] Stock status updates when switching variants

---

## API Response Examples

### Example 1: Product with Multiple Variants (2 sizes)

```json
{
  "id": 3,
  "sku": "Gentle Skin Cleanser (125 ml)",
  "name": "Gentle Skin Cleanser",
  "price": 403.26,
  "extension_attributes": {
    "brand_name": "Cetaphil",
    "variants": [
      {
        "product_id": 4,
        "sku": "Gentle Skin Cleanser (125 ml)",
        "name": "Gentle Skin Cleanser",
        "ml_size": "125",
        "price": 429.00,
        "special_price": 403.26,
        "final_price": 403.26,
        "is_in_stock": true,
        "salable_qty": 1.0,
        "image": "http://localhost:8080/media/catalog/product/f/4/f47002_1.jpg"
      },
      {
        "product_id": 3,
        "sku": "Gentle Skin Cleanser (250 ml)",
        "name": "Gentle Skin Cleanser",
        "ml_size": "250",
        "price": 769.00,
        "special_price": 722.86,
        "final_price": 722.86,
        "is_in_stock": true,
        "salable_qty": 0.0,
        "image": "http://localhost:8080/media/catalog/product/f/4/f47005_1_1.jpg"
      }
    ]
  }
}
```

**UI Behavior:**
- ✅ Show variant selector with options: "125 ml" and "250 ml"
- ✅ Default selection: 125 ml
- ✅ Display price: ₹403.26 (with ₹429 crossed out)
- ✅ When 250ml selected: Update price to ₹722.86

### Example 2: Product with Single Variant (1 size)

```json
{
  "id": 100,
  "sku": "Single Product (100 ml)",
  "name": "Single Product",
  "price": 599.00,
  "extension_attributes": {
    "brand_name": "Brand Name",
    "variants": [
      {
        "product_id": 100,
        "sku": "Single Product (100 ml)",
        "name": "Single Product",
        "ml_size": "100",
        "price": 599.00,
        "special_price": null,
        "final_price": 599.00,
        "is_in_stock": true,
        "salable_qty": 50.0,
        "image": "http://localhost:8080/media/catalog/product/..."
      }
    ]
  }
}
```

**UI Behavior:**
- ✅ Show "100 ml" as static text (no selector)
- ✅ Display price: ₹599 (no discount)
- ✅ Show "Add to Cart" button normally

---

## Questions or Issues?

### Common Questions:

**Q: What if I need the full product details for a variant?**
A: Call `GET /rest/V1/products/{variant-sku}` - it returns the complete product with all attributes.

**Q: How do I know which variant is currently shown?**
A: The main product's SKU in the listing is the "smallest ML" variant. Check the `variants` array for all options.

**Q: Can I filter by specific ML size?**
A: No direct filter exists. Filter on frontend after receiving the `variants` array.

**Q: Will pagination break?**
A: No. `total_count` now reflects unique products, and `pageSize` returns that many unique products.

**Q: What if product has no ML in SKU?**
A: It still gets a `variants` array with 1 item where `ml_size` will be `null`.

---

## Summary

### Key Points for Frontend:
1. ✅ **Always check** `extension_attributes.variants` array
2. ✅ **Show selector** only if `variants.length >= 2`
3. ✅ **Use `final_price`** for display (handles special pricing automatically)
4. ✅ **Use selected variant's SKU** when adding to cart
5. ✅ **Check `is_in_stock` and `salable_qty`** before enabling Add to Cart
6. ✅ **Default to first variant** (smallest ML size)

### Breaking Changes:
- ⚠️ `total_count` now reflects unique products (not total SKUs)
- ⚠️ Product listings return fewer items (variants consolidated)
- ✅ All product SKUs still accessible via single product API

---

**Document Version:** 1.0
**Last Updated:** 2025-12-10
**Backend Module:** Formula_ProductVariants v1.0.0
