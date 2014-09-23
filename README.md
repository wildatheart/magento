magento
=======

 Adyen Payment plugin for Magento.

 Current release version is 2.1.0

 2.1.0
 Features & Fixes
 * Show OneClick payments in Magento checkout
 * New API payment method SEPA
 * Add discount for the payment method open invoice (karna/Afterpay)
 * Optimized UI in the payment step (for the default OnePage checkout and the OneStepCheckout module)
 * Build in scan functionality for using Magento as cash register solution
 * express checkout button for payments through the Adyen Shuttle
 * Creditcard installments can now be setup foreach creditcard type
 * Installment rate (in %) is now added to the installment setup
 * For Klarna it is now possible to show date of birth and gender only when you select payment method open invoice
 * Multicurrency problem with Api Payments solved
 * Show reservationNumber for Klarna and AfterPay in the payment information of the order
 * Directory lookup call to retrieve the payment methods shown in the payment step can now be cached for better performance
 * Payment methods can now be sorted in the front-end
 * Boleto firstname and lastname automatically filled in based on billing information
 * For Boleto payments the paid amount is now shown in the payment information of the order detail page
 * Possible to select different status for Boleto if the payment is overpaid or underpaid
 * Full refund will send in Cancel\Refund request to Adyen if payment is not yet captured the order will be cancelled
 * For payment method Klarna and after pay the fields are disabled on the Adyen HPP
 * Payment methods in the checkout give back the language specific payment method text
 * Add after pay logo in magenta checkout
 * Plugin version number and links for setting up now visible in the Adyen General settings sections

 2.0.3
 Features & Fixes
 
 * HPP payment method selection now automatically loaded from the selected skin (Adyen calls this directory lookup)
 * Module namespace and module name is now Adyen/Payment instead of Madia/Adyen
 * CSE now better support for the onestepcheckout modules as well
 * CSE IE8 Fix
 * Possible to make Cash payments

 Older versions:
 
 1.0.0.8
 Features and Fixes:

 * Recurring contract default is ONECLICK and fully changeable
 * OpenInvoice payment method supports afterpay and Klarna
 * API payments available in the backend
 * Bank transfer (incl. international) added as HPP payment options
 * Adyen AUTHORIZATION status (succeeded/failed) visible in order overview
 * Saving configuration removes spaces in front and end of input value
 * DeliveryDate for Boleto payments is now configurable
 * Completion email is now triggered when notification AUTHORIZATION is received
 * Added payment method POS. Automatic redirect to Adyen app for the payment
 * Payment to applicable countries now available for all payment methods
 * Fixed time-out in notification (removed the filelock system). 

 1.0.0.7

 Features:
 * CC with client side encryption
 * CC with 3D secure
 * new Boleto payment method (Brazilian market)

 Fixes:
 * notification receival now works properly on NFS
 * improved logging
 
 
 
