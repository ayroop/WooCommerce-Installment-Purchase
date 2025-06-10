# WooCommerce Installment Purchase

A WordPress plugin that enables installment purchases for WooCommerce products with smart calculation and verification system.

## Features

- Two payment options on product pages:
  - Cash Payment
  - Installment Payment
- Smart calculation of installment purchase:
  - Minimum 50% down payment
  - Service fee on remaining balance
  - Up to 6 months installment period
- Application process:
  - Personal information collection
  - Bank account verification
  - Terms and conditions agreement
  - Inquiry fee payment
- Admin features:
  - Application management
  - Status updates
  - Email notifications
  - Check registration
- Multi-language support (including Persian)

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce 5.0 or higher
- Composer for dependency management

## Installation

1. Download the plugin from GitHub
2. Upload the plugin files to `/wp-content/plugins/woocommerce-installment-purchase`
3. Install dependencies:
   ```bash
   cd wp-content/plugins/woocommerce-installment-purchase
   composer install
   ```
4. Activate the plugin through the WordPress admin panel
5. Configure the plugin settings in WooCommerce > Settings > Payments

## Configuration

1. Go to WooCommerce > Settings > Payments
2. Enable "Installment Purchase"
3. Configure the following settings:
   - Service Fee (%)
   - Maximum Months
   - Inquiry Fee
   - Email Templates
   - Terms and Conditions

## Usage

### For Customers

1. Visit a product page
2. Choose between Cash or Installment payment
3. If selecting Installment:
   - Fill out the application form
   - Pay the inquiry fee
   - Wait for approval
   - Complete the down payment
   - Submit checks for remaining installments

### For Administrators

1. Access applications in WooCommerce > Installment Applications
2. Review and process applications
3. Update application status
4. Register checks
5. Monitor installment payments

## Development

### Project Structure

```
woocommerce-installment-purchase/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
├── languages/
│   ├── installment-purchase-fa_IR.po
│   └── installment-purchase.pot
├── src/
│   ├── Admin/
│   │   └── Admin.php
│   ├── API/
│   │   └── API.php
│   ├── Core/
│   │   ├── Activator.php
│   │   ├── Deactivator.php
│   │   ├── Loader.php
│   │   └── Plugin.php
│   ├── Frontend/
│   │   └── Frontend.php
│   └── Gateway/
│       └── Gateway.php
├── composer.json
├── installment-purchase.php
└── README.md
```

### Building from Source

1. Clone the repository:
   ```bash
   git clone https://github.com/ayroop/WooCommerce-Installment-Purchase.git
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Build assets (if needed):
   ```bash
   npm install
   npm run build
   ```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Support

For support, please:
1. Check the [documentation](https://github.com/ayroop/WooCommerce-Installment-Purchase/wiki)
2. Open an [issue](https://github.com/ayroop/WooCommerce-Installment-Purchase/issues)
3. Contact the maintainer

## Credits

- Developed by [Ayroop](https://github.com/ayroop)
- Built with [WooCommerce](https://woocommerce.com/) 