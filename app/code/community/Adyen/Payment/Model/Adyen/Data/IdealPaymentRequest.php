<?php

/**
 * Adyen Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category	Adyen
 * @package	Adyen_Payment
 * @copyright	Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_Model_Adyen_Data_IdealPaymentRequest extends Adyen_Payment_Model_Adyen_Data_Abstract {
    public $entranceCode;
    public $issuerId;
    public $language;
    public $merchantReturnUrl;
    
    public function __construct() {
        $this->amount = new Adyen_Payment_Model_Adyen_Data_Amount();
    }    
    
    public function create(Varien_Object $payment, $amount, $order, $paymentMethod = null, $merchantAccount = null) {
        $incrementId = $order->getIncrementId();
        $orderCurrencyCode = $order->getOrderCurrencyCode();
        $customerId = $order->getCustomerId();
        $currency = $order->getOrderCurrencyCode();

        $this->reference = $incrementId;
        $this->merchantAccount = $merchantAccount;
        $this->amount->currency = $orderCurrencyCode;
        $this->amount->value = Mage::helper('adyen')->formatAmount($amount, $currency);

        //shopper data
        $customerEmail = $order->getCustomerEmail();
        $this->shopperEmail = $customerEmail;
        $this->shopperIP = $order->getRemoteIp();
        $this->shopperReference = $customerId;
        
        //IDEAL
        $this->entranceCode = $order->getQuoteId();
        $id = explode(DS, $payment->getInfoInstance()->getPoNumber());
        $this->issuerId = $id[0];
        $this->language = 'nl';
        $this->merchantReturnUrl = Mage::app()->getStore()->getBaseUrl().'checkout/onepage/success';

        return $this;
    }    
}