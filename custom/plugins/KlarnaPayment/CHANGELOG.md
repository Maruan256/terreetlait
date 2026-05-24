# 4.0.8
- Fixed: excessive log entries in the scheduled task for authorising orders.

# 4.0.7
- Authorization callback improved and fixed.
- Added logs.
- Compatibility for displaying the payment badges has been added for the Zenit theme.

# 4.0.6
- Compatibility for displaying payment names has been added for the Zenit theme.
- Better exception handling added for the order validator.

# 4.0.5
- SIWK fix: An error occurs when a customer tries to sign up with the same account used on another sales channel.

# 4.0.4
- Fixed: the Express button was visible for unavailable products.
- Added: automated check that fixes orders stuck in the wrong payment status.

# 4.0.3
- Fixed: unable to update from SW6 to SW7

# 4.0.2
- Bug fix for the composable frontend: For orders with multiple payment attempts, changes were written to the wrong transaction.

# 4.0.1
- Fixed: the wrong badge was being displayed in the footer.

# 4.0.0
- Merged all payment methods into one: "Pay with Klarna."
- Removed installation wizard

# 3.4.5
- Missing labels in the settings page have been fixed.

# 3.4.4
- Fix Express-Checkout: add 'acceptedDataProtection' flag
- Fix SIWK: add 'acceptedDataProtection' flag

# 3.4.3
- Added config: hide the legal form dropdown
- Added config: hide the registration ID field

# 3.3.3
- Fix: Don't initialize the session if the cart is empty in Composable Frontends.
- Fix OrderValidator: Do not validate empty shipping addresses.

# 3.3.2
- Fixed: Missing variable prevents orders from being placed in the Composable Frontend

# 3.3.1
- Fixed logout after payment in composable frontend
- Fixed Klarna Hosted Payment Page triggered with non-Klarna payment in composable frontend

# 3.3.0
- A new plugin configuration has been added: 'Disable line item validation'

# 3.2.1
- Improved logging functionality

# 3.2.0
- Sign in with Klarna for 6.7
- Added default values in some API requests

# 3.0.1
- Composable Frontends (SW 6.7): Use of store-api routes instead of Controller after Hosted Payment Page redirect

# 3.0.0
- Added compatibility with Shopware Version 6.7

# 2.3.2
- Composable Frontends: Use of store-api routes instead of Controller after Hosted Payment Page redirect

# 2.3.1
- Removed unwanted Composable Frontends logs

# 2.3.0
- Added compatibility with Composable Frontend (Headless Shopware).
- Fixed: the Express Checkout button remains deactivated until a product variant has been selected.
- Fixed: it could happen that the name was not displayed in the checkout for Klarna payment methods.

# 2.2.8
- Fix: removed outdated code

# 2.2.7
- Added the option to disable address validation between Shopware and Klarna. 

# 2.2.6
- For some orders, the authorisation callback was triggered several times.

# 2.2.5
- Could not place express checkout order, when phone number is required for registration.

# 2.2.4
- When ordering with Klarna, it could happen that the shipping costs are displayed several times.
- If you filter the orders in the backend by delivery status "open", orders with the status "shipped" are also incorrectly displayed.

# 2.2.3
- Fixed problem: Order-update API

# 2.2.2
- Fixed problem hash validation

# 2.2.1
- Extended Klarna log-entries

# 2.2.0
- Removed Salutations from address structs (solves the problem when loading the Klarna details)
- Fixed Error: As soon as the billing address is not a European address, the purchase button was blocked.
- Extended logs

# 2.1.2
- Fixed problem carthash validation

# 2.1.1
- When the postcode is missing, an error occurs.
- Once the order is partially captured and then fully captured, the status is not changed from partially paid to paid.
- There were problems with the session when validating the address.
- Orders with digital products could not be processed properly.

# 2.1.0
- Fixing the validation problem when updating/changing the delivery address

# 2.0.1
- Fixed error: Articles could not be cancelled in the administration.

# 2.0.0
- Added Compatibility with Shopware Version 6.6

# 1.14.0
- Fixed an error in the checkout when API credentials are missing
- Optimized the determination of the correct endpoint for requests to the Klarna API
- Added new payment method which will be automatically activated once the merchant account is migrated at Klarna

# 1.13.0
- Removed Klarna Express Button
- Added Klarna Express Checkout
- Fixed the display of the Klarna logo for the payment methods in the account section
- Fixed the display of custom fields in the administration
- Added return status of SwagCommercial to refund modal
- Fixed the translation of shipping method name in the order process
- Fixed decoration of CheckoutController for compatiblity to other plugins

# 1.12.1
- Fixed the writing of the debug log with Shopware 6.5.4+
- Fixed compatibility to other plugins that decorate the CheckoutController
- Fixed compatibility when reinstalling the plugin
- Fixed the payment method change to Klarna for an existing order

# 1.12.0
- Added saving of custom fields to order addresses for shops below 6.4.14.0
- Added integration with Billie for handling B2B orders
- Added synchronization of the Klarna billing address to Shopware when finishing the order. Changes to the billing address in Shopware won't be synchronized with Klarna anymore. 

# 1.11.0
- Added the handling of the authorization callback
- Fixed the restriction to one payment method when using Klarna Express
- Changed the release process for building one plugin version that is compatible with Shopware 6.4 and Shopware 6.5

# 1.10.0
- Added check for selected Klarna Express before preselecting payment method of Klarna
- Fixed the rounding of amounts
- Fixed the overwriting of the payment description
- Fixed the display of a custom image for a payment method
- Fixed the addition of the cookies in the required group
- Fixed the handling when changing the payment method of an order
- Changed usage of custom fields on orders to dedicated EntityExtension to avoid issues with other plugins
- Changed synchronization with Klarna so order positions won't be synced with Klarna once the order is captured

# 1.9.0
- Added compatibility with Shopware 6.5.0.0
- Adjusted the display of the payment names in the checkout

# 1.8.0
- Fixed translation of error message when adjusting order in administration
- Added product variant information in capture/refund modal
- Fixed error that occurred by closing the order history detail modal
- Optimize code to reduce possible errors
- Injected environment variable `APP_SECRET` via Symfony service definition in respective services to fix LogicException in checkout.
- Added compatibility for USA
- Fixed overwrite of administration component `sw-order-detail-base` for other plugins

# 1.7.0
- Added Klarna Express Login functionality to the checkout
- Added session update request to reduce session creation with Klarna
- Fixed validation of tos checkbox in checkout

# 1.6.3
- Added a check if terms and conditions were accepted in the checkout

# 1.6.2
- Added compatibility with CSRF mode "ajax"

# 1.6.1
- Added compatibility with Shopware 6.4.10.0

# 1.6.0
- Added NetiNextEasyCoupon compatibility. Vouchers will be transferred to Klarna as gift cards.
- Updated minimum version to Shopware 6.3.0.0
- Added notice for inheritance of plugin configuration for Shopware versions lower 6.3.4.0
- Fixed the de- and activation of the button for order completion with Klarna payment methods
- Fixed transition from `authorized` to `paid` status with manual full capture
- Removed Rule installer due to overwriting of custom rules
- Removed the explicit conditions for payment methods
- Fixed the cover URL for order requests to the Klarna API
- Added the product URL for order requests to the Klarna API
- Fixed the available payment methods depending on the response from the Klarna API 

# 1.5.0
- Moved language files to new location
- Added Ireland to available countries
- Optimized the display of inherited values in the plugin configuration
- Set order payment status to authorized if payment is authorized by Klarna
- Use selected shipping method name as order position for Klarna order
- Added SwagCustomizedProducts compatibility

# 1.4.4
- Fix usage of vouchers with multiple tax rates
- Fix handling of the payment finish modal, when being closed
- The Klarna widget is now displayed above the position table in the checkout if at least Shopware 6.4 is used
- Fix the button for order completion when closing the Klarna modal
- Fix eslint error message with Shopware 6.4.5.x

# 1.4.3
- Fix uninstall of plugin when user data should not be kept

# 1.4.2
- Added support for PHP 8
- Update cart hash logic for verification of order editing

# 1.4.1
- Added compatibility with Shopware 6.4

# 1.4.0
- Removed instant Shopping.
- Added translations for payment methods.
- Fix error in Internet Explorer that prevents order completion

# 1.3.8
- Removed Klarna payments rule from Rule-Builder.
- Added Italy as a supported country. Note: Instant Shopping is not supported for Italy.
- Fix rare error for invalid values when saving plugin configuration.  
- Automatic captures will only be executed if the fraud status is accepted.
- Automatic capture for delivery and order status open is now possible.

# 1.3.7
- Fix plugin configuration value errors
- Please note: There are known erroneous configuration behaviours with sales channel inheritance in the documentation

# 1.3.6
- Improve error handling for Instant Shopping
- Fix missing labels in plugin configuration
- Added note in documentation (https://klarna.kellerkinder.de/en/1-3/index.html#h-translation-of-the-payment-methods) about translation of payment methods.

# 1.3.5
- Add default name for payment methods for the installation with a different system language

# 1.3.4
- Fix handling of promotions in cart

# 1.3.3
- Fix automatic package of zip archive to include compiled files again
- Fix use of wrong exception class

# 1.3.2
- Fix styling issues of the Instant Shopping button
- Fix error on updating order when order has been captured
- Improve performance in checkout and order update

# 1.3.1
- Fix error when selecting an order from the search results

# 1.3.0
- Add locale and currency to the rule for available Klarna payment methods in the checkout
- Send shipping tracking information to the Klarna merchant portal  
- Fix error during installation if a language could not be found
- Fix Instant Shopping for guests with activated required registration settings
- Optimize validation of API credentials

# 1.2.1
- Fix delivery information display on detail pages
- Fix administration order updates for non-Klarna orders

# 1.2.0
- Added compatibility with Shopware 6.2

# 1.1.0
- Implementation of Klarna instant shopping
- Add support for net prices (starting from Shopware 6.2.0)

# 1.0.4
- Fix checkout confirmation button for non-Klarna payment methods

# 1.0.3
- Fix customer name for Klarna Payment session

# 1.0.2
- Added combined Pay Now payment method and Credit Card
- Order changes in the administration are verified with Klarna before persisting the changes
- Changed address structure
- Payment method categories can now be disabled from the plugin configuration
- Skip verification of order changes during order process before Klarna order is created

# 1.0.1
- Fixed a rounding issue for Klarna calls

# 1.0.0
- First version of the Klarna Payment integration for Shopware 6.1
