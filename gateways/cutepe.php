<?php
/**
 * CUTEPE PAYMENT MODULE FOR WHMCS
 * @author CutePe
 * @Website https://cutepe.com/
 * @license GPL V.1 
 * @Disclaimer Please do not temper the code 
 * settings.
 *
 * @return array
 */
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function cutepe_MetaData()
{
    return array(
        'DisplayName' => 'CutePe Gateway',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function cutepe_config() {
    $configarray = array(
        "FriendlyName" => array("Type" => "System", "Value" => "CutePe"),
        "cutepe_api_key" => array("FriendlyName" => "CutePe API Key", "Type" => "password", "Size" => "50", "Placeholder" => "YOUR_API_KEY"),
        "merchant_key" => array("FriendlyName" => "Merchant Key", "Type" => "text", "Size" => "30", "Placeholder" => "paytm, phonepe, etc."),
    );
    return $configarray;
}


function cutepe_link($params) {
    // Module configuration parameters
    $api_url = "https://merchants.cutepe.com/api/orders/create-order";
    $api_key = $params['cutepe_api_key'];
    $merchant_key = $params['merchant_key'];

    // Invoice and client details
    $order_id = $params['invoiceid'] . '_' . time();
    $amount = $params['amount'];
    $description = "Hosting Payment";
    $client_name = $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'];
    $client_email = $params['clientdetails']['email'];
    $client_phone = (strlen($params['clientdetails']['phonenumber']) == 10) ? $params['clientdetails']['phonenumber'] : '9999999999';

    // Callback URL
    $callback_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]";
    $callback_url = str_replace('cart.php', 'modules/gateways/callback/cutepe_callback.php', $callback_url);
    $callback_url = str_replace('viewinvoice.php', 'modules/gateways/callback/cutepe_callback.php', $callback_url);

    // API request payload
    $data = array(
        'txn_id' => $order_id,
        'amount' => $amount,
        'p_info' => $description,
        'customer_name' => $client_name,
        'customer_email' => $client_email,
        'customer_mobile' => $client_phone,
        'redirect_url' => $callback_url,
        'udf1' => $params['companyname'],
        'udf2' => '',
        'udf3' => '',
        'merchant_key' => $merchant_key
    );

    // API request options
    $options = array(
        'http' => array(
            'header' => "Content-Type: application/json\r\n" .
                        "Authorization: Bearer $api_key\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        )
    );

    $context = stream_context_create($options);
    $response = file_get_contents($api_url, false, $context);

    // Handle API response
    if ($response !== false) {
        $response_data = json_decode($response, true);

        if (isset($response_data['status']) && $response_data['status'] == 'success') {
            $payment_url = $response_data['payment_url'];
            
            $code = '<form method="GET" action="' . $payment_url . '">';
            $code .= '<input type="submit" value="Pay Now with CutePe" />';
            $code .= '</form>';

            return $code;
        } else {
            $error_message = isset($response_data['message']) ? $response_data['message'] : 'Unknown error occurred.';
            return "<b style='color:red;'>Error: $error_message</b>";
        }
    } else {
        return "<b style='color:red;'>API connection failed. Please try again later.</b>";
    }
}
