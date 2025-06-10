# WooCommerce Installment Purchase

A WordPress plugin that enables installment purchases for WooCommerce products.

## Features

- Add "Cash" or "Installment" payment options on product pages
- Customizable down payment percentage
- Configurable service fee
- Flexible installment period (up to 6 months)
- Application management system
- Email notifications
- Admin dashboard for managing applications
- REST API endpoints for integration
- Fully translatable

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Activate the plugin
5. Go to WooCommerce > Settings > Payments
6. Enable and configure the "Installment Purchase" payment method

## Configuration

### General Settings

1. Go to WooCommerce > Settings > Payments
2. Click on "Installment Purchase" to configure:
   - Enable/Disable the payment method
   - Set minimum down payment percentage
   - Configure service fee
   - Set maximum number of months
   - Set inquiry fee amount

### Application Page

1. Go to Pages > Add New
2. Add the shortcode `[installment_application]` to the page content
3. Publish the page
4. Go to WooCommerce > Settings > Installment Purchase
5. Set the Application Page ID

## Usage

### For Customers

1. Browse products on your store
2. On the product page, select "Installment" payment option
3. Click "Add to Cart"
4. Fill out the application form
5. Submit the application
6. Wait for approval
7. Once approved, complete the down payment
8. Receive the product
9. Pay monthly installments

### For Administrators

1. Go to WooCommerce > Installment Applications
2. View all applications
3. Approve or decline applications
4. Monitor payment status
5. Send notifications to customers

## Translation

The plugin is fully translatable. To translate:

1. Use a translation plugin like Loco Translate or WPML
2. Create a new translation for the text domain 'installment-purchase'
3. Translate all strings
4. Save the translation files in the 'languages' directory

## API Endpoints

The plugin provides the following REST API endpoints:

- `GET /wp-json/wc-installment/v1/applications` - List all applications
- `GET /wp-json/wc-installment/v1/applications/{id}` - Get application details
- `POST /wp-json/wc-installment/v1/applications/{id}/status` - Update application status

## Support

For support, please:

1. Check the [documentation](https://ayrop.com)
2. Visit our [support forum](https://ayrop.com)
3. Contact us at support@ayrop.com

## Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Developed by [Pooriya](https://ayrop.com)
- Built with [WooCommerce](https://woocommerce.com) 