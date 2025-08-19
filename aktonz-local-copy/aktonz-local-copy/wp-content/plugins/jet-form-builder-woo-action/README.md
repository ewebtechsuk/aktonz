# JetFormBuilder Woocommerce Cart & Checkout Action
Premium Addon for JetFormBuilder & JetEngine Forms

# ChangeLog

## 1.0.8
* Fix: Ensure JetFormBuilder form fields are correctly passed to WooCommerce checkout
* FIX: exclude 'WC.ORDER.COMPLETE', 'WC.CHECKOUT.COMPLETE' events from 'Redirect to Page' action

## 1.0.7
* UPD: make woo action compatible with >= 3.4.0 JetFormBuilder

## 1.0.6
* ADD: Event `WC.ORDER.COMPLETE` for post-submit actions in JetFormBuilder
* ADD: `_woocom_order_id` computed field

## 1.0.5
* ADD: Event `WC.CHECKOUT.COMPLETE` for post-submit actions in JetFormBuilder
* FIX: Hide empty fields in order details
* FIX: Fatal error with additional Woocommerce addon

## 1.0.4
* FIX: Use product price by default
* UPD: Delay redirect to Woo Checkout

## 1.0.3
* Tweak: Removed unnecessary hook

## 1.0.2
* ADD: View order details on checkout
* Tweak: add license manager

## 1.0.1
* FIX: php notices

## 1.0.0
* Initial release