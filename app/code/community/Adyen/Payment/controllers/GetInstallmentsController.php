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

class Adyen_Payment_GetInstallmentsController extends Mage_Core_Controller_Front_Action {

    public function indexAction()
    {

        $params = $this->getRequest()->getParams();

        // get installments for cctype
        $ccType = $params['ccType'];

        // get installments
        $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();

        if($quote->isVirtual()) {
            $address = $this->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }

        // distract the already included added fee for installment you selected before
        if($address->getBasePaymentInstallmentFeeAmount() > 0) {
            $amount = (double) ($quote->getGrandTotal() - $address->getBasePaymentInstallmentFeeAmount());
        } else {
            $amount = (double) $quote->getGrandTotal();
        }

        $currency = $quote->getQuoteCurrencyCode();


        // this creditcard type is selected
        if($ccType != "") {

            // installment key where installents are saved in settings
            $ccTypeInstallments = "installments_".$ccType;

            // check if this type has installments configured
            $all_installments = Mage::helper('adyen/installments')->getInstallments(null, $ccTypeInstallments);

            if(empty($all_installments)) {
                // no installments congigure fall back on default
                $ccTypeInstallments = null;
            } else {
                $max_installments = Mage::helper('adyen/installments')->getConfigValue($currency,$amount, null, $ccTypeInstallments);
            }
        }

        // Fallback to the default installments if creditcard type has no one configured
        if($ccTypeInstallments == null) {
            $max_installments = Mage::helper('adyen/installments')->getConfigValue($currency,$amount, null);
            $all_installments = Mage::helper('adyen/installments')->getInstallments();
        }

        // result array here
        for($i=1;$i<=$max_installments;$i++){

            // check if installment has extra interest
            $key = $i-1;
            $installment = $all_installments[$key];
            if(isset($installment[3]) && $installment[3] > 0) {
                $total_amount_with_interest = $amount + ($amount * ($installment[3] / 100));
            } else {
                $total_amount_with_interest = $amount;
            }

            $partial_amount = ((double)$total_amount_with_interest)/$i;
            $result[(string)$i] = $i."x ".$currency." ".number_format($partial_amount,2);
        }

        $jsonData = json_encode($result);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);



    }

}