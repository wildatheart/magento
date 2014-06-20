magento
=======

 Adyen Payment plugin for Magento.

 This is development version 2.1.0
 
 Current release version is 2.0.3
 
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
 
 
 
