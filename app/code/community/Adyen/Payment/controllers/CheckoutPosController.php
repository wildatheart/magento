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

class Adyen_Payment_CheckoutPosController extends Mage_Core_Controller_Front_Action {

    public function indexAction()
    {

        $customer = Mage::getSingleton('customer/session');

        // only proceed if customer is logged in
        if($customer->isLoggedIn()) {

            // get email
            $params = $this->getRequest()->getParams();
            $adyenPosEmail = isset($params['adyenPosEmail']) ? $params['adyenPosEmail'] : "";
            $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();

            // get customer object from session
            $customerObject = Mage::getModel('customer/customer')->load($customer->getId());

            // important update the shippingaddress and billingaddress this can be null sometimes.
            $quote->assignCustomerWithAddressChange($customerObject);

            // update email with customer Email
            if($adyenPosEmail != "") {
                $quote->setCustomerEmail($adyenPosEmail);
            }

            $shippingAddress = $quote->getShippingAddress();

            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
                ->setShippingMethod('freeshipping_freeshipping')
                ->setPaymentMethod('adyen_pos');

            $quote->getPayment()->importData(array('method' => 'adyen_pos'));
            $quote->collectTotals()->save();
            $session = Mage::getSingleton('checkout/session');

            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();
            $order = $service->getOrder();

            $oderStatus = Mage::helper('adyen')->getOrderStatus();
            $order->setStatus($oderStatus);
            $order->save();

            // add order information to the session
            $session->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastSuccessQuoteId($order->getQuoteId());

            $this->_redirect('adyen/process/redirect');
            return $this;
        } else {
            Mage::throwException('Customer is not logged in.');
        }
    }
}