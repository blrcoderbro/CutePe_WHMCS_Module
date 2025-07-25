## CutePe Payment Gateway for WHMCS

Integrate CutePe payment gateway with WHMCS to accept payments securely.

### What's New in Version 2.0

- **SignUp & Login Added**: Users can now sign up and log in directly through the CutePe module interface.
- **Webhook URL Added**: Simplified webhook URL configuration for seamless callback handling.
- **Payment Button Changed**: Redesigned payment button for better user experience and accessibility.
- **SQL Import Automated**: SQL database import is now automated during installation, reducing setup time.

## Features

- Easy setup and integration.
- Secure payment processing.
- Automatic invoice updates on successful payments.
- Handles payment failures gracefully.

## Installation

1. Upload and extract the zip file to your WHMCS installation's `modules` directory.

2. Enable CutePe module in WHMCS admin panel.

    ![Finding CutePe in Apps](https://i.ibb.co/pBj8N6LM/2025-07-12-15-15.png)

    ![CutePe WHMCS Module Activation in WHMCS](https://i.ibb.co/8Dz4K041/2025-07-12-15-17.png)

3. Enter API Key, Merchant Key in Module Settings.

    ![CutePe WHMCS Module Settings](https://i.ibb.co/Y4DdbXs2/2025-07-12-15-21.png)

4. Copy and paste callback URL into CutePe Merchant Dashboard.


## Configuration

- **API Key**: Obtain from CutePe Merchant Dashboard.
- **Merchant Key**: Provided by CutePe.
- **API URL**: Endpoint for transaction verification.

## Callback Handling

CutePe sends transaction status updates via callbacks. Use `cutepe_callback.php` to process these updates.

### Example Callback URL

```
https://yourdomain.com/modules/gateways/callback/cutepe_callback.php?status=success&order_id=12345&hash=abc123
```

## Troubleshooting

- Missing parameters: Ensure `status`, `order_id`, and `hash` are included.
- Invalid invoice ID: Verify invoice exists in WHMCS.
- API errors: Check server connectivity and API endpoint.

## Support

Contact [support@cutepe.com](mailto:support@cutepe.com) for assistance.

## License

Licensed under the [MIT License](LICENSE).
