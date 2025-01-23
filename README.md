Certainly! Here’s the updated **README.md** with the file copy instructions included:

```markdown
# CutePe Payment Gateway Integration for WHMCS

This repository provides integration for the **CutePe** payment gateway with **WHMCS** (Web Hosting Management and Billing System). The integration enables your WHMCS system to accept payments via CutePe's platform, including handling callback responses, verifying transactions, and marking invoices as paid.

## Features

- **Easy Integration**: Simplified process to integrate CutePe with WHMCS.
- **Secure Payment Processing**: Secure API calls and transaction handling.
- **Automatic Invoice Updates**: Automatically marks invoices as paid upon successful payment.
- **Failure Handling**: Redirects users to appropriate failure pages in case of errors.

## Prerequisites

- **WHMCS Version**: WHMCS 7.x or later.
- **PHP Version**: PHP 7.2 or later.
- **CutePe Merchant Account**: You need a CutePe merchant account and an API key for integration.
- **Access to WHMCS Admin**: You need administrative access to the WHMCS backend.

## Installation

1. **Copy Files**:
   - Copy the **`cutepe.php`** file into your WHMCS installation’s `/modules/gateways/` folder.
   - Copy the **`cutepe_callback.php`** file into your WHMCS installation’s `/modules/gateways/callback/` folder.
   - Copy the entire **`cutepe`** folder into the `/modules/gateways/` folder.

2. **Configure Gateway**:
   - Log in to your WHMCS Admin panel.
   - Go to **Setup** > **Payments** > **Payment Gateways**.
   - Find **CutePe** in the list and click **Activate**.
   - Enter your **API Key** and other necessary configuration details such as merchant key, etc.

3. **API Configuration**:
   - You can configure API-related settings such as **CutePe API URL** and **API Key** in the gateway settings section.
   - Ensure that your **API Key** is entered correctly. This key is used to authenticate requests to the CutePe API.

4. **Callback URL**:
   - Make sure to set up the **Callback URL** in your CutePe Merchant Dashboard.
   - The callback URL should point to `https://yourdomain.com/modules/gateways/callback/cutepe_callback.php`.

5. **SQL Manual Integration**:
   - If you got some error related Sql then Add the SQL Manually from gateways/sql.sql file into your phpmyadmin



## Configuration Parameters

Here are the required parameters for configuring the CutePe Gateway in WHMCS:

- **API Key**: Your **CutePe API Key**.
- **Merchant Key**: Your **merchant key** provided by CutePe.
- **API URL**: Endpoint for verifying the transaction status.

You can access these parameters through the WHMCS admin interface when configuring the CutePe gateway.

## Handling Payment Notifications (Callbacks)

Once a payment is made, CutePe sends a callback to the WHMCS server to notify about the transaction's status.

### Callback URL

The callback URL is set to the **`cutepe_callback.php`** file inside the gateway module.

- **Callback URL Format**:
    ```
    https://yourdomain.com/modules/gateways/callback/cutepe_callback.php?status=success&order_id=1234567890&hash=somehashvalue
    ```

### Sample Response Format

Here’s an example of what the response data might look like:

```json
{
    "status": "success",
    "txn_status": "TXN_SUCCESS",
    "message": "Transaction completed successfully",
    "data": {
        "client_txn_id": "91837181039181718",
        "customer_name": "Customer Name",
        "customer_email": "customer@mail.com",
        "customer_mobile": "9999999999",
        "p_info": "Product Information",
        "amount": 100,
        "upi_txn_id": "1829178261092",
        "status": "SUCCESS",
        "remark": "Transaction completed successfully",
        "udf1": "User defined field 1",
        "udf2": "User defined field 2",
        "udf3": "User defined field 3",
        "redirect_url": "https://google.com?status=success&order_id=order_123456789&hash=hashhere",
        "txnAt": "16-01-2025",
        "createdAt": "2025-01-15T22:27:26.677Z",
        "Merchant": {
            "name": "CompanyName",
            "upi_id": null
        }
    }
}
```

### Handling the Callback Response

Once the callback is received:

1. The `order_id` is used to check the transaction status via the CutePe API.
2. If the status is `TXN_SUCCESS`, the invoice is marked as paid.
3. If the status is not successful, the user is redirected to a failure page.

### Error Handling

In case of an error (e.g., network failure, invalid response, tampered data), the user will be redirected to the client area with a failure message.

## Example Code for Callback Handling

```php
<?php
require_once '../../../init.php';
require_once '../../../includes/gatewayfunctions.php';
require_once '../../../includes/invoicefunctions.php';

$gatewayModuleName = 'cutepe';
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$status = $_GET['status'] ?? null;
$orderId = $_GET['order_id'] ?? null;
$receivedHash = $_GET['hash'] ?? null;

$redirectUrl = $gatewayParams['systemurl'] . "clientarea.php?action=invoices";

if (!$status || !$orderId || !$receivedHash) {
    logTransaction($gatewayParams['name'], $_GET, "Invalid Callback: Missing Parameters");
    header("Location: {$redirectUrl}&paymentfailed=true&error=missing_params");
    exit;
}

if ($status != 'success') {
    logTransaction($gatewayParams['name'], $_GET, "Payment Failed");
    header("Location: {$redirectUrl}&paymentfailed=true&error=payment_failed");
    exit;
}

$apiUrl = 'https://merchants.cutepe.com/api/orders/check-order-status';
$apiKey = $gatewayParams['cutepe_api_key'];

$data = array('order_id' => $orderId);
$options = array(
    'http' => array(
        'header' => "Content-Type: application/json\r\n" .
                    "Authorization: Bearer $apiKey\r\n",
        'method' => 'POST',
        'content' => json_encode($data),
    )
);

$context = stream_context_create($options);
$response = file_get_contents($apiUrl, false, $context);

if ($response === false) {
    logTransaction($gatewayParams['name'], $_GET, "API Error: Unable to Verify Order Status");
    header("Location: {$redirectUrl}&paymentfailed=true&error=api_error");
    exit;
}

$responseData = json_decode($response, true);

if (!isset($responseData['status']) || $responseData['status'] !== 'success') {
    logTransaction($gatewayParams['name'], $responseData, "API Error: Invalid Response");
    header("Location: {$redirectUrl}&paymentfailed=true&error=invalid_api_response");
    exit;
}

$txnStatus = $responseData['txn_status'] ?? 'UNKNOWN';

if ($txnStatus === 'TXN_SUCCESS') {
    try {
        $invoiceId = checkCbInvoiceID($orderId, $gatewayParams['name']);
    } catch (Exception $e) {
        logTransaction($gatewayParams['name'], ['error' => $e->getMessage()], "Invalid Invoice ID");
        $redirectUrl = $gatewayParams['systemurl'] . "clientarea.php?action=invoices&paymentfailed=true&error=invalid_invoice";
        header("Location: $redirectUrl");
        exit;
    }

    $transactionId = $responseData['data']['upi_txn_id'];
    $paymentAmount = $responseData['data']['amount'];

    addInvoicePayment($invoiceId, $transactionId, $paymentAmount, 0, $gatewayModuleName);
    logTransaction($gatewayParams['name'], $responseData, "Successful");

    header("Location: {$redirectUrl}&paymentsuccess=true");
    exit;
} else {
    logTransaction($gatewayParams['name'], $responseData, "Transaction Failed");
    header("Location: {$redirectUrl}&paymentfailed=true&error=txn_failed");
    exit;
}
?>
```

## Troubleshooting

- **Missing Parameters**: Ensure all required parameters (`status`, `order_id`, `hash`) are included in the callback URL.
- **Invalid Invoice ID**: Check if the invoice exists in the WHMCS database.
- **API Errors**: If the CutePe API is unreachable or returns an error, ensure your server has internet access and the API endpoint is correct.

## Support

For any issues or questions regarding the integration, please contact support at [support@cutepe.com](mailto:support@cutepe.com).

## License

This integration is open-source and free to use under the [MIT License](LICENSE).

---

Feel free to customize this file as needed, and keep it updated based on any changes in the CutePe API or WHMCS modules.
```

This update includes detailed instructions for copying the necessary files to the corresponding folders in the WHMCS installation. It should now be easier to set up the CutePe payment gateway integration. Let me know if you'd like further tweaks!