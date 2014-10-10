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
class Adyen_Payment_Helper_Data extends Mage_Payment_Helper_Data {

    public function getCcTypes() {
        $_types = Mage::getConfig()->getNode('default/adyen/payment/cctypes')->asArray();
        uasort($_types, array('Mage_Payment_Model_Config', 'compareCcTypes'));
        $types = array();
        foreach ($_types as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }

    public function getBoletoTypes() {
        $_types = Mage::getConfig()->getNode('default/adyen/payment/boletotypes')->asArray();
        $types = array();
        foreach ($_types as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }

    public function getOpenInvoiceTypes() {
        $_types = Mage::getConfig()->getNode('default/adyen/payment/openinvoicetypes')->asArray();
        $types = array();
        foreach ($_types as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }

    public function getRecurringTypes() {
        $_types = Mage::getConfig()->getNode('default/adyen/payment/recurringtypes')->asArray();
        $types = array();
        foreach ($_types as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }

    public function getExtensionVersion() {
        return (string) Mage::getConfig()->getNode()->modules->Adyen_Payment->version;
    }

    public function hasEnableScanner() {
        return (int) Mage::getStoreConfig('payment/adyen_pos/enable_scanner');
    }

    public function hasAutoSubmitScanner() {
        return (int) Mage::getStoreConfig('payment/adyen_pos/auto_submit_scanner');
    }

    public function hasExpressCheckout() {
        return (int) Mage::getStoreConfig('payment/adyen_pos/express_checkout');
    }

    public function hasCashExpressCheckout() {
        return (int) Mage::getStoreConfig('payment/adyen_pos/cash_express_checkout');
    }

    public function getOrderStatus() {
        return Mage::getStoreConfig('payment/adyen_abstract/order_status');
    }

    /**
     * @param Mage_Sales_Model_Quote | Mage_Sales_Model_Order $object
     */
    public function isPaymentFeeEnabled($object)
    {
        $fee = Mage::getStoreConfig('payment/adyen_openinvoice/fee');
        $paymentMethod = $object->getPayment()->getMethod() ;
        if ($paymentMethod == 'adyen_openinvoice' && $fee > 0) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * @param Mage_Sales_Model_Quote | Mage_Sales_Model_Order $object
     */
    public function getPaymentFeeAmount($object)
    {
        return Mage::getStoreConfig('payment/adyen_openinvoice/fee');
    }

    public function formatAmount($amount, $currency) {

        // check the format
        switch($currency) {
            case "JPY":
            case "IDR":
            case "KRW":
            case "BYR":
            case "VND":
            case "CVE":
            case "DJF":
            case "GNF":
            case "PYG":
            case "RWF":
            case "UGX":
            case "VUV":
            case "XAF":
            case "XOF":
            case "XPF":
            case "GHC":
                $format = 0;
                break;
            case "MRO":
                $format = 1;
                break;
            case "BHD":
            case "JOD":
            case "KWD":
            case "OMR":
            case "LYD":
            case "TND":
                $format = 3;
                break;
            default:
                $format = 2;
                break;
        }

        return number_format($amount, $format, '', '');
    }

}
