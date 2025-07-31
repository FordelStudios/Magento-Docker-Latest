# Formula Shiprocket Module

This module provides integration with Shiprocket API for courier serviceability checks in Magento 2.

## Features

-   Admin configuration panel for Shiprocket credentials
-   Secure password storage with encryption
-   API serviceability checking
-   Debug logging capabilities
-   Console command for testing configuration

## Installation

1. Ensure the module is properly installed in your Magento 2 installation
2. Run the following commands:

```bash
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

## Configuration

### Admin Panel Configuration

1. Go to **Admin Panel** → **Stores** → **Configuration**
2. Navigate to **Shipping Methods** → **Shiprocket Configuration**
3. Configure the following settings:

    - **Enable Shiprocket Integration**: Enable/disable the module
    - **Shiprocket Email**: Your Shiprocket account email address
    - **Shiprocket Password**: Your Shiprocket account password (encrypted)
    - **Pickup Postcode**: The postcode from where shipments will be picked up
    - **Debug Mode**: Enable detailed logging for troubleshooting

4. Click **Save Config**

### API Endpoint

The module provides a REST API endpoint for checking courier serviceability:

**Endpoint**: `POST /rest/V1/shiprocket/serviceability`

**Parameters**:

-   `pincode` (string): Delivery pincode
-   `cod` (boolean): Cash on delivery (true/false)
-   `weight` (float): Package weight in kg

**Example Request**:

```json
{
    "pincode": "400001",
    "cod": false,
    "weight": 1.5
}
```

**Example Response**:

```json
{
    "success": true,
    "data": {
        "available_courier_companies": [
            {
                "courier_name": "DTDC",
                "rate": 150,
                "estimated_delivery_days": "3-5",
                "cod": 1
            }
        ]
    }
}
```

## Console Commands

### Test Configuration

Test the module configuration and API connectivity:

```bash
# Test configuration only
php bin/magento shiprocket:test-configuration

# Test with specific pincode
php bin/magento shiprocket:test-configuration --pincode=400001 --weight=1.5 --cod=0
```

### Test Serviceability

Test the serviceability API directly:

```bash
php bin/magento shiprocket:test-serviceability --pincode=400001 --weight=1.5 --cod=0
```

## Security

-   Passwords are encrypted using Magento's built-in encryption
-   ACL (Access Control List) is implemented for admin configuration
-   API responses are logged for debugging purposes

## Troubleshooting

### Common Issues

1. **Authentication Failed**

    - Verify your Shiprocket email and password
    - Check if your Shiprocket account is active

2. **Configuration Validation Failed**

    - Ensure all required fields are filled in admin configuration
    - Verify email format is valid

3. **API Call Failed**
    - Check network connectivity
    - Verify Shiprocket API endpoints are accessible
    - Enable debug mode for detailed logging

### Debug Logging

When debug mode is enabled, detailed logs are written to:

-   `var/log/system.log`
-   `var/log/exception.log`

### Permissions

Ensure the admin user has the following permissions:

-   **Stores** → **Configuration** → **Shiprocket Configuration**

## Support

For issues and questions, please check:

1. Magento logs in `var/log/`
2. Debug mode output
3. Console command test results

## Version Compatibility

-   Magento 2.4.x
-   PHP 7.4+
-   Shiprocket API v2
