# API Documentation - Routine Set Products

## Overview
The `is_routine_set` attribute is a custom boolean product attribute that can be queried via Magento's REST API using search criteria filters.

## Authentication

All API requests require authentication. Magento supports several methods:

### Admin Token (for testing)
```bash
POST http://localhost:8080/rest/V1/integration/admin/token
Content-Type: application/json

{
  "username": "admin_username",
  "password": "admin_password"
}
```

Response: `"your-admin-token"`

### Customer Token
```bash
POST http://localhost:8080/rest/V1/integration/customer/token
Content-Type: application/json

{
  "username": "customer@email.com",
  "password": "customer_password"
}
```

## Fetching Products with is_routine_set Enabled

### Endpoint
```
GET /rest/V1/products
```

### Method 1: Search Criteria API (Recommended)

**Get products where is_routine_set = Yes (1)**

```bash
GET http://localhost:8080/rest/V1/products?searchCriteria[filterGroups][0][filters][0][field]=is_routine_set&searchCriteria[filterGroups][0][filters][0][value]=1&searchCriteria[filterGroups][0][filters][0][conditionType]=eq
Authorization: Bearer your-token-here
```

**URL Parameters Breakdown:**
- `searchCriteria[filterGroups][0][filters][0][field]=is_routine_set` - Field to filter on
- `searchCriteria[filterGroups][0][filters][0][value]=1` - Value (1 = Yes, 0 = No)
- `searchCriteria[filterGroups][0][filters][0][conditionType]=eq` - Condition type (equals)

### Method 2: URL Encoded (Clean format)

```
GET /rest/V1/products?
  searchCriteria[filterGroups][0][filters][0][field]=is_routine_set
  &searchCriteria[filterGroups][0][filters][0][value]=1
  &searchCriteria[filterGroups][0][filters][0][conditionType]=eq
```

### Method 3: With Pagination

**Get first 10 routine set products:**

```bash
GET http://localhost:8080/rest/V1/products?searchCriteria[filterGroups][0][filters][0][field]=is_routine_set&searchCriteria[filterGroups][0][filters][0][value]=1&searchCriteria[filterGroups][0][filters][0][conditionType]=eq&searchCriteria[pageSize]=10&searchCriteria[currentPage]=1
Authorization: Bearer your-token-here
```

**Parameters:**
- `searchCriteria[pageSize]=10` - Number of items per page
- `searchCriteria[currentPage]=1` - Current page number (1-indexed)

### Method 4: Combining Multiple Filters

**Get routine set products that are enabled and in stock:**

```bash
GET http://localhost:8080/rest/V1/products?
  searchCriteria[filterGroups][0][filters][0][field]=is_routine_set
  &searchCriteria[filterGroups][0][filters][0][value]=1
  &searchCriteria[filterGroups][0][filters][0][conditionType]=eq
  &searchCriteria[filterGroups][1][filters][0][field]=status
  &searchCriteria[filterGroups][1][filters][0][value]=1
  &searchCriteria[filterGroups][1][filters][0][conditionType]=eq
  &searchCriteria[filterGroups][2][filters][0][field]=visibility
  &searchCriteria[filterGroups][2][filters][0][value]=4
  &searchCriteria[filterGroups][2][filters][0][conditionType]=eq
Authorization: Bearer your-token-here
```

**Note:** Different filter groups create AND conditions. Multiple filters within the same group create OR conditions.

### Method 5: With Sorting

**Get routine set products sorted by name:**

```bash
GET http://localhost:8080/rest/V1/products?
  searchCriteria[filterGroups][0][filters][0][field]=is_routine_set
  &searchCriteria[filterGroups][0][filters][0][value]=1
  &searchCriteria[filterGroups][0][filters][0][conditionType]=eq
  &searchCriteria[sortOrders][0][field]=name
  &searchCriteria[sortOrders][0][direction]=ASC
Authorization: Bearer your-token-here
```

**Sort Parameters:**
- `searchCriteria[sortOrders][0][field]=name` - Field to sort by
- `searchCriteria[sortOrders][0][direction]=ASC` - Direction (ASC or DESC)

## Response Structure

### Success Response (200 OK)

```json
{
  "items": [
    {
      "id": 123,
      "sku": "ROUTINE-SET-001",
      "name": "Complete Face Care Routine",
      "attribute_set_id": 4,
      "price": 99.99,
      "status": 1,
      "visibility": 4,
      "type_id": "simple",
      "created_at": "2024-11-20 10:30:00",
      "updated_at": "2024-11-25 15:45:00",
      "extension_attributes": {
        "stock_item": {
          "item_id": 123,
          "product_id": 123,
          "stock_id": 1,
          "qty": 100,
          "is_in_stock": true
        },
        "face_routine_type_labels": "Cleanse, Treat, Hydrate",
        "hair_routine_type_labels": "",
        "body_routine_type_labels": "",
        "routine_timing_label": "Day"
      },
      "custom_attributes": [
        {
          "attribute_code": "is_routine_set",
          "value": "1"
        },
        {
          "attribute_code": "face_routine_type",
          "value": "cleanse-face,treat-face,hydrate-face"
        },
        {
          "attribute_code": "routine_timing",
          "value": "0"
        },
        {
          "attribute_code": "description",
          "value": "Complete face care routine set"
        }
      ]
    }
  ],
  "search_criteria": {
    "filter_groups": [
      {
        "filters": [
          {
            "field": "is_routine_set",
            "value": "1",
            "condition_type": "eq"
          }
        ]
      }
    ]
  },
  "total_count": 5
}
```

### Key Response Fields:

- **items**: Array of product objects
- **total_count**: Total number of products matching the criteria
- **custom_attributes**: Contains `is_routine_set` value
  - `"1"` = Yes (is a routine set)
  - `"0"` = No (not a routine set)
- **extension_attributes**: Contains computed labels for routine types

## JavaScript/TypeScript Example

### Using Fetch API

```javascript
const MAGENTO_BASE_URL = 'http://localhost:8080';
const AUTH_TOKEN = 'your-admin-or-customer-token';

async function getRoutineSetProducts(page = 1, pageSize = 20) {
  const params = new URLSearchParams({
    'searchCriteria[filterGroups][0][filters][0][field]': 'is_routine_set',
    'searchCriteria[filterGroups][0][filters][0][value]': '1',
    'searchCriteria[filterGroups][0][filters][0][conditionType]': 'eq',
    'searchCriteria[pageSize]': pageSize.toString(),
    'searchCriteria[currentPage]': page.toString()
  });

  const response = await fetch(
    `${MAGENTO_BASE_URL}/rest/V1/products?${params}`,
    {
      headers: {
        'Authorization': `Bearer ${AUTH_TOKEN}`,
        'Content-Type': 'application/json'
      }
    }
  );

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const data = await response.json();
  return data;
}

// Usage
getRoutineSetProducts(1, 10)
  .then(response => {
    console.log('Total routine sets:', response.total_count);
    console.log('Products:', response.items);
  })
  .catch(error => console.error('Error:', error));
```

### Using Axios

```javascript
import axios from 'axios';

const magentoAPI = axios.create({
  baseURL: 'http://localhost:8080/rest/V1',
  headers: {
    'Authorization': `Bearer ${AUTH_TOKEN}`,
    'Content-Type': 'application/json'
  }
});

async function getRoutineSetProducts(page = 1, pageSize = 20) {
  try {
    const response = await magentoAPI.get('/products', {
      params: {
        'searchCriteria[filterGroups][0][filters][0][field]': 'is_routine_set',
        'searchCriteria[filterGroups][0][filters][0][value]': '1',
        'searchCriteria[filterGroups][0][filters][0][conditionType]': 'eq',
        'searchCriteria[pageSize]': pageSize,
        'searchCriteria[currentPage]': page
      }
    });

    return response.data;
  } catch (error) {
    console.error('Error fetching routine set products:', error);
    throw error;
  }
}
```

## PHP Example (For Backend/Testing)

```php
<?php

$baseUrl = 'http://localhost:8080/rest/V1';
$token = 'your-admin-or-customer-token';

function getRoutineSetProducts($token, $page = 1, $pageSize = 20) {
    global $baseUrl;

    $params = http_build_query([
        'searchCriteria' => [
            'filterGroups' => [
                [
                    'filters' => [
                        [
                            'field' => 'is_routine_set',
                            'value' => '1',
                            'conditionType' => 'eq'
                        ]
                    ]
                ]
            ],
            'pageSize' => $pageSize,
            'currentPage' => $page
        ]
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$baseUrl/products?$params");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return json_decode($response, true);
    }

    throw new Exception("API request failed with status code: $httpCode");
}

// Usage
try {
    $result = getRoutineSetProducts($token, 1, 10);
    echo "Total routine sets: " . $result['total_count'] . "\n";
    foreach ($result['items'] as $product) {
        echo "- {$product['sku']}: {$product['name']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## cURL Examples

### Basic Request

```bash
curl -X GET "http://localhost:8080/rest/V1/products?searchCriteria[filterGroups][0][filters][0][field]=is_routine_set&searchCriteria[filterGroups][0][filters][0][value]=1&searchCriteria[filterGroups][0][filters][0][conditionType]=eq" \
  -H "Authorization: Bearer your-token-here" \
  -H "Content-Type: application/json"
```

### With Pretty Print (using jq)

```bash
curl -X GET "http://localhost:8080/rest/V1/products?searchCriteria[filterGroups][0][filters][0][field]=is_routine_set&searchCriteria[filterGroups][0][filters][0][value]=1&searchCriteria[filterGroups][0][filters][0][conditionType]=eq&searchCriteria[pageSize]=5" \
  -H "Authorization: Bearer your-token-here" \
  -H "Content-Type: application/json" | jq '.'
```

## GraphQL Alternative

If your Magento instance has GraphQL enabled, you can also query products using GraphQL:

```graphql
{
  products(
    filter: {
      is_routine_set: { eq: "1" }
    }
    pageSize: 20
    currentPage: 1
  ) {
    items {
      id
      sku
      name
      price_range {
        minimum_price {
          final_price {
            value
            currency
          }
        }
      }
      is_routine_set
      face_routine_type
      hair_routine_type
      body_routine_type
      routine_timing
    }
    page_info {
      page_size
      current_page
      total_pages
    }
    total_count
  }
}
```

**Endpoint:**
```
POST http://localhost:8080/graphql
Authorization: Bearer your-token-here
Content-Type: application/json
```

## Getting a Single Product by SKU

To check if a specific product is a routine set:

```bash
GET http://localhost:8080/rest/V1/products/{sku}
Authorization: Bearer your-token-here
```

The response will include `is_routine_set` in the `custom_attributes` array.

## Condition Types Reference

Available condition types for filters:
- `eq` - Equals
- `neq` - Not equals
- `like` - Like (for text search with %)
- `nlike` - Not like
- `in` - In array
- `nin` - Not in array
- `gt` - Greater than
- `lt` - Less than
- `gteq` - Greater than or equal
- `lteq` - Less than or equal
- `null` - Is null
- `notnull` - Is not null

## Common Status & Visibility Values

**Status:**
- `1` = Enabled
- `2` = Disabled

**Visibility:**
- `1` = Not Visible Individually
- `2` = Catalog
- `3` = Search
- `4` = Catalog, Search

## Error Responses

### 401 Unauthorized
```json
{
  "message": "The consumer isn't authorized to access %resources.",
  "parameters": {
    "resources": "Magento_Catalog::products"
  }
}
```

**Solution:** Check your authentication token.

### 400 Bad Request
```json
{
  "message": "Invalid attribute name: is_routine_set"
}
```

**Solution:** Ensure the attribute exists and setup:upgrade has been run.

## Testing the Attribute

### Set a product as routine set via API

```bash
PUT http://localhost:8080/rest/V1/products/{sku}
Authorization: Bearer your-admin-token
Content-Type: application/json

{
  "product": {
    "sku": "PRODUCT-SKU-HERE",
    "custom_attributes": [
      {
        "attribute_code": "is_routine_set",
        "value": "1"
      }
    ]
  }
}
```

## Additional Resources

- [Magento REST API Documentation](https://devdocs.magento.com/guides/v2.4/rest/bk-rest.html)
- [Search Criteria Documentation](https://devdocs.magento.com/guides/v2.4/rest/performing-searches.html)
- [Product API Reference](https://magento.redoc.ly/2.4.7-admin/#tag/products)
