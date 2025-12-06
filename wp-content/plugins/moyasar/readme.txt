=== Moyasar ===
Contributors: moyasar
Tags: Gateway, Payment, Credit Card, Apple Pay, STC Pay
Requires at least: 4.6
Tested up to: 6.8
Stable tag: 7.3.3
Requires PHP: 5.6
Language: en_US, ar
URI: https://moyasar.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Moyasar Payment Gateway, Adds credit card, Apple Pay, and STC Pay payment capabilities to Woocommerce.

== Description ==

= Payments with Ease =
A comprehensive set of payment solutions that allows you to easily accept and track your transactions.

= Accept e-Payments in your WooCommerce store using Moyasar's plugin. =


== Features ==
- Credit Card (Visa, MasterCard, Mada and American Express)
- Apple Pay
- STC Pay
- Samsung Pay


This plugin support both Classic and Blocks:

- Blocks: Developed using React JS.
- Classic: Implemented natively.


== Third-Party Services ==

This plugin relies on the following third-party services:

1. Moyasar Payment Gateway
   Moyasar API is used for processing payments and communicating transaction data.

2. Apple Pay
   The Apple Pay SDK is loaded from Apple's CDN to enable Apple Pay functionality in supported browsers.

3. Samsung Pay
   The Samsung Pay SDK is loaded from Samsung's CDN to enable Samsung Pay functionality in supported browsers.

By using this plugin, you agree to the terms and conditions of these third-party services.


== F. A. Q. ==

For more information, please visit: our website https://moyasar.com


== Screenshots ==

1. blocks.png
2. classic.png


== Changelog ==

= v7.3.3 =
- Fixed: trigger WC()->cart->calculate_totals when applying a coupon to ensure accurate totals

= v7.3.2 =

- Features
Apple Pay: Support Apple Pay on the web.
- Fixes
UI: Fixed credit card icons layout on small devices to prevent overflow/misalignment.



= v7.3.1 =
- Fixed: 3-D Secure modal watcher now handles stores that redirect `/checkout_mysr` to `/`.
     It no longer relies on an exact path match, preventing the modal from hanging.

= v7.3.0 =
- Feature: (Subscription) -
Implemented Subscription function in Credit Card method
Always save the token in the database (after payment and in general)

- Fixed :(Coupon) - Reworked tryApplyCouponToOrder to add the product instead of previous behavior

= v7.2.3 =
- Fixed: localization issues

= v7.2.2 =
- Fixed: (Blocks) Credit Card form not displaying correctly
- Fixed: submit button localization
- Improved: Samsung Pay logs

= v7.2.1 =
- Fixed: Missing Files

= v7.2.0 =
- Added: Support for Samsung Pay as a payment method.
- Added: Display of the payment amount and SAR currency on the payment button.
- Improved: General performance and stability enhancements.

= v7.1.15 =
- Fixed: Resolved an issue when applying a coupon code if the tax is included.

= v7.1.14 =

- Added: Compatibility with Cloudflare Rocket Loader by ignoring specific Apple Pay JavaScript.

= v7.1.13 =

- Fixed: Prevented multiple requests from being sent to fetch new order details in the Order class.
- Improved: Enabled direct redirection when 3D Secure (3DS) is not required.


= v7.1.12 =

- Fixed: Removed the unregisterForm method from the moyasarApplePayClassic class to address issues affecting Credit Card functionality.

= v7.1.11 =

- Added: Support for data-cfasync="false" attributes on script tags to ensure compatibility with Cloudflare Rocket Loader.

= v7.1.10 =

- General Fixes: Minor stability and compatibility improvements.

= v7.1.9 =

- Fixed: Resolved coupon code conflicts (Moyasar & Wordpress) that interfered with the checkout process.

= v7.1.8 =

- Fixed (i18n): Addressed STC Pay localization issues.

= v7.1.7 =

- Fixed: Prevented orders from being updated twice, ensuring accurate order management.

= v7.1.6 =

- Code Quality: Added more inline documentation for better developer understanding.

= v7.1.5 =

- Improvements: General enhancements for stability and performance.

= v7.1.4 =

- Fixed: Resolved conflicts with other plugins.

= v7.1.3 =

- General Fixes: Addressed various WordPress-specific issues and refinements.

= v7.1.2 =

- Fixed: Corrected JavaScript errors that occurred in certain checkout scenarios.

= v7.1.1 =

- Fixed: Addressed JavaScript issues affecting front-end interactions.
- Fixed: Resolved a refund-related issue for more reliable payment handling.

= v7.1.0 =

- Added: Popup modal for 3D Secure (3DS) in Classic mode, enhancing user experience during authentication.
- Fixed: Issues with creating the webhook secret key.
- Fixed: Card name placeholder now displays correctly.
- Fixed: Resolved conflicts with other plugins to maintain compatibility.
- Fixed: Addressed various Apple Pay popup issues to ensure a smoother checkout process.

= v7.0.0 =

- Feature: Implemented built-in payment forms for Credit Card, Apple Pay, and STC Pay.
- Compatibility: Introduced support for both Classic and Block editor environments.
- Blocks: Developed using React.js for a modern, dynamic user interface.
- Classic: Implemented native code for backward compatibility and performance.
