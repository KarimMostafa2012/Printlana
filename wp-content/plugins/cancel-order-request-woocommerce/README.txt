=== Cancel order / Refund request for WooCommerce ===
Contributors: rajeshsingh520
Donate link: piwebsolution.com
Tags: order again, re-order, cancel order, woocommerce cancel order, refund
Requires at least: 3.0.1
Tested up to: 6.8
Stable tag: 1.3.4.19
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Order cancellation request / Refund request / Return order request. Repeat order option to customer for WooCommerce

== Description ==

Replace WooCommerce cancel order button with order cancellation request button, here is what this plugin can do for you

&#9989; You can enable an order refund request button based on the **order status**; this will replace the WooCommerce cancel order button
&#9989; If you want to **replace the order cancellation button** with this order cancellation request button then activate this button on orders with status Pending and Failed
&#9989; Users can **add a reason** why they want to cancel the order
&#9989; **Admin will receive an email** with the order cancellation request and the reason for cancellation
&#9989; **Display a custom note to your customers** when they try to send a cancellation request.
&#9989; Make the reason a **required field**
&#9989; Admin can either decide to **cancel the order** or move it **back to Processing state**
&#9989; If Admin marks the order as Cancelled the **user will be sent an email** stating their order cancellation request was accepted
&#9989; If Admin moves the order status to Processing or Complete then the user will get an **email stating their cancellation request was denied**
&#9989; **Hide the cancellation request button** after a certain period of time.
&#9989; Give **a list of cancellation reasons** for users to select from
&#9989; View order **detail link added in the email sent to the customer**; you have the option to add this link for registered customers, guest customers, or both
&#9989; **Guest customers can request order cancellation** from the link given in the order detail page (Thank You page)
&#9989; Cancellation reason is **automatically added** in the **Order note**
&#9989; Auto refund as a store credit discount coupon
&#9989; Give the customer the option to accept a direct refund as a store credit discount coupon; this way you can keep the customer's money in your store


= Repeat Order option =
With our extension you can integrate and display the button “re-order”, “repeat order” on the overview page. 

This allows your customer to place the same order easily without going through your site to find the same product again, which they purchased in the past.

&#9989; Enable the repeat order button based on the order status or on all orders
&#9989; If the customer's cart is empty it will directly put the product in the **customer's cart**
&#9989; If the customer has other products in the cart, they are given the option of either **merging them with their cart or replacing their existing cart**
&#9989; If an ordered product no longer exists it gives the name of the product that can't be added to the cart (remaining products will be added)
&#9989; If the product variation has changed then it gives a message that **they need to add that product manually**
&#9989; Options to customize the text of the button and message shown

== Get pro version == 

[Buy Pro](https://www.piwebsolution.com/cart/?add-to-cart=13147&variation_id=15708) | [Try pro version on test site](http://websitemaintenanceservice.in/cancel_demo/) | [Documentation](https://www.piwebsolution.com/user-documentation-cancel-order-request-for-woocommerce/)

Pro version offers all the features of the free version plus these extra features:
&#9989; Allows your customer to place **a partial order cancellation request**: for example, if they ordered products A & B and only want to cancel product A, they can place a cancellation request for product A only.
&#9989; Disable the cancellation option for a specific product: mark product A as non-cancellable so users won't be able to place a cancellation request for that product.
&#9989; Allow users to upload an image along with a cancellation request
&#9989; Give the option to withdraw a cancellation request
&#9989; Disable the cancellation request option based on the payment method
&#9989; Disable the cancellation request option based on the customer group
&#9989; Set the default action on repeat order
&#9989; Redirect to cart or checkout page once repeat order products are added to the cart
&#9989; Admin will get an email that shows the product and the quantity the user has requested to cancel
&#9989; Customer will also get an email stating their cancellation request has been submitted; it will also show the product quantities the user has requested to cancel.
&#9989; Auto refund in TerraWallet
&#9989; Give the customer the option to accept a direct refund in their Wallet (TerraWallet) or as a store credit discount coupon



== Frequently Asked Questions ==

= Can I still have order cancellation button =
Yes, you can have an order cancellation / refund button. The order cancellation button is shown for orders with status Failed or On-hold, so you can make the request button show for other order statuses.

= Can I show Cancel request button for Processing order =
Yes. Just select the Processing status in the plugin settings and it will show for orders with status Processing.

= How to allow customer to send reason for the order return along with the cancellation request =
Yes. This option is available in the plugin by default. When the customer clicks the cancel order button they will be asked to enter a reason for the order cancellation.

= Where can I see the order return reason =
The admin will receive the order cancellation reason in the email, and can see the reason on the order page as well.

= How admin will know above the order cancellation request =
The admin will receive an email and can see the request in the dashboard.

= How to decline the order cancellation request =
By changing the status of the order from Cancel Request to Processing or Complete.

= How to accept the order cancellation request =
By changing the order status to the Cancelled state we can mark the order as cancelled.

= How customer will know his cancellation request was decline =
When you change the order status to Complete or Processing, the user will receive an email stating their order cancellation request was declined.

= How customer will know if his order cancellation request was accepted =
The customer will receive an email stating their cancellation request for the order was accepted.

= I want to change the wordings of the email =
You can do that using the translation file. All text is translation-supported; we have added the language POT file.

= I want to show custom note to the customer when they want to cancel order =
Yes. You can specify a special message that will be displayed to the customer when they click the cancel order button.

= I want to hide the order cancellation request button after certain period of time =
Yes. You can set the time in minutes after placing the order, after which the order cancellation option will no longer be available.

= I want to give a list of cancellation reasons to customer to select from =
Yes. Add multiple reasons, one per line, and they will be shown in the cancellation request form to the customer.

= There is Click to view order details link in the email send to the customer =
This link is added by the plugin so customers can easily view their order details outside the email.

= Can Guest customer send a order cancellation request =
Yes, they can send cancellation request too.

= From where does the guest customer send cancellation request =
They have the cancellation request link on the Thank You page. The link for the order detail page is also added in the email sent to guest customers so they can go to the Thank You page and access the cancellation request button.

= Don't want customer to submit cancellation request with reason =
There is an option in the plugin to make the reason a required field; when enabled the user must either select a reason or describe the reason.

= I want to show repeat order button on order that failed =
Yes you can enable the repeat order button on specific order status, or on all the orders

= What will happen to the product that are in user cart when repeat order is clicked =
If their cart is empty it will directly put the product in the cart and show a success message. If they have products in their cart it will give the option to either merge the products into the existing cart or replace the existing cart products.

= What if their old order has some product that is out of stock or not sold any more =
In that case the plugin will add all other products to the cart and inform the customer that certain products can't be added to the cart.

= Will it show order again button on the View order and Order success page =
Yes, you also have the option to disable order again button as well

= Can user directly cancel order instead of waiting for the admin approval =
At present there is no such configuration in the plugin, but you can add the code below in your theme's functions.php file to directly mark the order as cancelled:
`add_filter('pisol_corw_cancel_request_new_status',function($status){ return 'cancelled'; } );`

= Cancelled order reason will be auto added in the Order note =
Yes. The plugin will automatically add the order cancellation reason in the order note.

= Can I disable reason addition in Order note =
Yes. You can disable cancellation reason addition in the order note by using the filter function below:
`add_filter('pisol_corw_order_note_filter', '__return_false');`

= Allow image upload option =
Pro version allows users to upload one image file with the order cancellation reason

= Don't want to give order cancellation request option if payment is Cash on delivery (or some specific payment method) =
This is available in the Pro version; it allows you to disable the cancellation request option for orders whose payments were made through a specific payment method.

= I want to disable the cancellation request option for specific group of customer =
This is available in the Pro version; it allows you to disable the cancellation option based on the user role.

= Does Repeat Order / Reorder plugin share any of my data or my customer data? (GDPR) =
No. Repeat Order will keep all your data inside your WordPress. No data is transmitted to us or any third-party service.

= Does it allow Partial cancellation of order =
This is available in the PRO version; it allows you to cancel part of an order. For example, if a customer ordered 2 units of Product A and 2 units of Product B and now doesn't want Product A and only wants 1 unit of Product B, they can place a cancellation request for the order.

= How to issue the refund for partial cancelled order =
You have to issue the refund using the WooCommerce refund method. It depends on the payment gateway used; if the gateway allows auto-refunds, it will be processed automatically when you issue the refund. If it does not allow auto-refunds, you must issue the refund manually via the payment gateway.

= Can it auto issue refund for cancellation request in Wallet =
In the Pro version you have the option to auto-issue refunds in TerraWallet. If you have the TerraWallet plugin installed and activated, you can enable this option and it will auto-issue the refund in TerraWallet.

= Can I give option to customer to accept refund in their Wallet =
In the Pro version you can give customers the option to accept refunds in their Wallet; if they accept, the refund will be issued to their Wallet (TerraWallet).

= Which wallet plugins are supported =
At present only TerraWallet is supported. If you use another wallet plugin and want integration, please contact us.

= Is it HPOS compatible =
Yes. Both the Free and PRO versions are HPOS compatible.

== Screenshots ==
1. Basic configuration of WooCommerce cancel order plugin
2. Different email template for WooCommerce cancel order plugin
3. Cancel order request button on the order detail page
4. Cancel order request form 
5. WooCommerce cancel order plugin email to admin
6. Cancellation request order status in the order list  
7. Cancellation reason shown in the order detail page
8. Cancel order request button on the order list page
9. Email sent to customer when order cancellation request is rejected
10. Email sent to customer when order cancellation request is accepted
11. Email sent to customer when a new order cancellation request is received
12. Customer can raise a cancellation request from the order detail page
13. Repurchase option
14. Popup shown when repeat order button is clicked 
15. WooCommerce cancel order plugin repeat order button on the order detail page
16. Pro version allows you to cancel specific product from the order 
17. Issue refund to the wallet 
18. WooCommerce order cancel plugin allows auto cancellation of orders
19. Auto refund in the customer wallet

== Changelog ==

= 1.3.4.9 =
* code optimized for cancel order request plugin

= 1.3.4.7 =
* Cancel order request plugin tested for WC 10.0.2

= 1.3.4.6 =
* Cancel order request plugin Tested for WC 9.9.5
* UI improvement

= 1.3.4.4 =
* WooCommerce cancel order plugin tested for WC 9.9.3

= 1.3.4.2 =
* Tested for WC 9.8.0

= 1.3.4.1 =
* Test for WP 6.8.0

= 1.3.3.79 =
* Tested for WC 9.7.0

= 1.3.3.77 =
* Tested for WC 9.6.2

= 1.3.3.76 =
* Tested for WC 9.6.0

= 1.3.3.74 =
* Tested for WC 9.5.0

= 1.3.3.73 =
* Auto refund option given in the free version

= 1.3.3.70 =
* nonce added in the form

= 1.3.3.69 =
* Tested for WC 9.4.0

= 1.3.3.67 =
* plugin list updated

= 1.3.3.66 =
* Tested for WP 6.7.0

= 1.3.3.63 =
* Tested for WC 9.3.0

= 1.3.3.62 =
* Tested for WC 9.2.3

= 1.3.3.61 =
* Tested for WC 9.2.0

= 1.3.3.60 =
* Tested for WC 9.1.4

= 1.3.3.49 =
* tested for WP 6.6.1

= 1.3.3.47 =
* Custom order no. shown in the error message as well 
* Tested for WP 6.6.0

= 1.3.3.46 =
* banner change
* support for custom order no.

= 1.3.3.44 =
* Tested for WC 9.0.0

= 1.3.3.43 =
* Tested for WC 8.9.3

= 1.3.3.39 =
* Compatible with PHP 8.2

= 1.3.3.37 =
* Tested for WC 8.7.0

= 1.3.3.32 =
* Tested for WP 6.4.3

= 1.3.3.31 =
* Tested for WC 8.5.2

= 1.3.3.22 =
* Tested for WC 8.2.0
 
= 1.3.3.21 = 
* Tested for WP 6.3.1

= 1.3.3.20 =
* Order link in admin email will link to the order edit page in the backend 

= 1.3.3.17 =
* Tested for WC 8.0.3

= 1.3.3.16 =
* Tested for WC 8.0.2

= 1.3.3.14 =
* Tested for WC 8.0.1

= 1.3.3.13 =
* Tested for WP 6.3.0

= 1.3.3.10 =
* HPOS compatible

= 1.3.3.6 =
* Tested for WP 6.2.2

= 1.3.3.3 =
* Tested for WC 7.6.1

= 1.3.3.1 =
* Admin order cancel email will be send when order status changes from Cancel request to Cancel state

= 1.3.3 =
* Tested for WP 6.2

= 1.3.1 =
* Email header showing link fixed

= 1.3.0 =
* Tested for WC 7.4.0

= 1.2.99 =
* Quick save option added in