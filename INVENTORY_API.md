# Inventory Management API

## Overview
This API provides endpoints for managing inventory items in the InHouse system.

## Authentication
All inventory endpoints require authentication using Sanctum. Include the authentication token in the `Authorization` header.

### Authentication Header
```
Authorization: Bearer {your_access_token}
```

## Endpoints

### 1. List All Inventory Items
- **URL:** `/api/inventory`
- **Method:** `GET`
- **Description:** Retrieve a list of all inventory items

#### Response
- **Success Response:**
  - **Code:** 200 OK
  - **Content:**
    ```json
    {
      "data": [
        {
          "id": 1,
          "name": "Product Name",
          "sku": "PRODUCT-SKU",
          "quantity": 100,
          "price": 19.99,
          "created_at": "2025-01-01T00:00:00.000000Z",
          "updated_at": "2025-01-01T00:00:00.000000Z"
        }
      ]
    }
    ```

### 2. Create Inventory Item
- **URL:** `/api/inventory`
- **Method:** `POST`
- **Description:** Create a new inventory item

#### Request Body
```json
{
  "name": "New Product",
  "sku": "NEW-PRODUCT-SKU",
  "quantity": 50,
  "price": 29.99
}
```

#### Validation Rules
- `name`: Required, string, max:255, alphanumeric with spaces, hyphens, dots
- `sku`: Required, unique, uppercase letters/numbers/hyphens, max:100
- `quantity`: Required, integer, min:0, max:10000
- `price`: Required, numeric, min:0, max:1,000,000, up to 2 decimal places

#### Response
- **Success Response:**
  - **Code:** 201 Created
  - **Content:** Created inventory item details

### 3. Get Specific Inventory Item
- **URL:** `/api/inventory/{id}`
- **Method:** `GET`
- **Description:** Retrieve details of a specific inventory item

#### Response
- **Success Response:**
  - **Code:** 200 OK
  - **Content:** Specific inventory item details

### 4. Update Inventory Item
- **URL:** `/api/inventory/{id}`
- **Method:** `PUT`
- **Description:** Fully update an existing inventory item

#### Request Body
```json
{
  "name": "Updated Product Name",
  "sku": "UPDATED-SKU",
  "quantity": 75,
  "price": 39.99
}
```

#### Response
- **Success Response:**
  - **Code:** 200 OK
  - **Content:** Updated inventory item details

### 5. Partially Update Inventory Item
- **URL:** `/api/inventory/{id}`
- **Method:** `PATCH`
- **Description:** Partially update an existing inventory item

#### Request Body (can include any combination of fields)
```json
{
  "price": 45.99,
  "quantity": 100
}
```

#### Response
- **Success Response:**
  - **Code:** 200 OK
  - **Content:** Updated inventory item details

### 6. Delete Inventory Item
- **URL:** `/api/inventory/{id}`
- **Method:** `DELETE`
- **Description:** Remove an inventory item

#### Response
- **Success Response:**
  - **Code:** 204 No Content

### 7. Adjust Inventory Quantity
- **URL:** `/api/inventory/{id}/adjust`
- **Method:** `POST`
- **Description:** Adjust the quantity of an inventory item

#### Request Body
```json
{
  "quantity": 10,
  "type": "add"  // or "subtract"
}
```

#### Response
- **Success Response:**
  - **Code:** 200 OK
  - **Content:** Updated inventory item details

### 8. Get Low Stock Items
- **URL:** `/api/inventory/low-stock`
- **Method:** `GET`
- **Description:** Retrieve inventory items with low stock (quantity <= 10)

#### Response
- **Success Response:**
  - **Code:** 200 OK
  - **Content:** List of low stock inventory items

### 9. Bulk Update Inventory
- **URL:** `/api/inventory/bulk-update`
- **Method:** `POST`
- **Description:** Update multiple inventory items in a single request

#### Request Body
```json
{
  "updates": [
    {"id": 1, "quantity": 50},
    {"id": 2, "quantity": 75}
  ]
}
```

#### Response
- **Success Response:**
  - **Code:** 200 OK
  - **Content:** 
    ```json
    {
      "message": "Bulk inventory update processed",
      "results": [
        {"id": 1, "status": "success"},
        {"id": 2, "status": "success"}
      ]
    }
    ```

## Error Handling
- **401 Unauthorized:** Authentication token is missing or invalid
- **422 Unprocessable Entity:** Validation errors
- **404 Not Found:** Requested resource not found

## Rate Limiting
- Default Laravel rate limiting applies
- Maximum of 60 requests per minute per authenticated user
