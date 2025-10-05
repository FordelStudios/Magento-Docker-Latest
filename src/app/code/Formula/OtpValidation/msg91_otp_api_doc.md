MSG91 OTP API Implementation Documentation

Overview

Switching from MSG91 SMS API to MSG91 SendOTP API to avoid DLT Template ID requirements for OTP messages.

Current State

-   Current Implementation: MSG91 SMS API (https://control.msg91.com/api/sendhttp.php)
-   Issue: Requires DLT Template ID (Error 211) for all SMS
-   Current File: Service/Msg91SmsService.php

Planned Changes

1. API Endpoint Changes

// OLD (SMS API)
$url = "https://control.msg91.com/api/sendhttp.php";

// NEW (OTP API)  
 $url = "https://api.msg91.com/api/v5/otp";

2. Request Parameters Update

// OLD Parameters
$postFields = [
'authkey' => $apiKey,
'mobiles' => $toNumber, // plural
'message' => $message,
'sender' => $senderId,
'route' => $route,
'DLT_TE_ID' => $dltTemplateId // Remove this
];

// NEW Parameters
$postFields = [
'authkey' => $apiKey,
'mobile' => $toNumber, // singular
'sender' => $senderId,
'otp' => $otpCode, // actual OTP code
'message' => $message // optional template
];

3. Method Signature Changes

// OLD
protected function sendViaCurl($apiKey, $senderId, $route, $dltTemplateId, $toNumber, $message)

// NEW  
 protected function sendViaCurl($apiKey, $senderId, $toNumber, $otpCode, $message)

4. Configuration Updates

-   Remove DLT Template ID requirement for OTP API mode
-   Add API mode toggle (SMS vs OTP)
-   Keep existing configuration structure

5. Files to Modify

1. Service/Msg91SmsService.php


    - Update sendViaCurl() method
    - Change API endpoint
    - Update parameters
    - Remove DLT validation for OTP mode

2. etc/adminhtml/system.xml (Optional)


    - Add API mode selection field
    - Make DLT Template ID conditional

3. etc/config.xml (Optional)


    - Add default API mode configuration

6. Response Handling Updates

// MSG91 OTP API returns different response format
// Need to update success/error detection logic

7. Error Handling Updates

-   Update parseDltErrorMessage() method for OTP API errors
-   Add OTP-specific error codes
-   Remove DLT-related error handling for OTP mode

Implementation Steps

1. Update sendViaCurl() method parameters and endpoint
2. Modify request payload for OTP API format
3. Update response parsing logic
4. Test with existing configuration
5. Add configuration options for API mode selection
6. Update error handling for OTP-specific responses

Testing Plan

1. Clear Magento cache
2. Test OTP sending with new API
3. Verify logs show correct API calls
4. Check MSG91 dashboard for delivery status
5. Test error scenarios

Rollback Plan

-   Keep Twilio integration as backup
-   Can revert to original SMS API if needed
-   All changes in single file, easy to rollback

Benefits Expected

-   ✅ No DLT Template ID required
-   ✅ Purpose-built for OTP delivery
-   ✅ Potentially better delivery rates
-   ✅ Simpler configuration

This documentation will help continue the implementation after the session limit.
