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
class Adyen_Payment_Model_Adyen_Ideal extends Adyen_Payment_Model_Adyen_Abstract {

    /**
     * @desc soap urls
     * @return string 
     */
    protected function _getAdyenUrls() {
        $test = array(
            'location' => "https://pal-test.adyen.com/pal/servlet/soap/Ideal",
            'wsdl' => "https://pal-test.adyen.com/pal/servlet/soap/Ideal?wsdl",
        );
        $live = array(
            'location' => "https://pal-live.adyen.com/pal/servlet/soap/Ideal",
            'wsdl' => "https://pal-live.adyen.com/pal/servlet/soap/Ideal?wsdl"
        );
        if ($this->getConfigDataDemoMode()) {
            return $test;
        } else {
            return $live;
        }
    }
    
    public function retrieveIdealIssuerList() {
        $this->_initService();
        $response = $this->_service->retrieveIdealIssuerList();
        //debug || log
        Mage::getResourceModel('adyen/adyen_debug')->assignData($response);
        $this->_debugAdyen();
        Mage::log($response, self::DEBUG_LEVEL, "adyen_ideal.log", true);
        
        return $response;
    }


    /**
     * Process beginIdealPayment {initiate ideal session}.If fail fall back to normal hpp page
     * @param type $payment
     * @param type $order
     * @return boolean 
     */
    public function beginIdealPayment($payment, $order) {
        try {
            $this->_initService();
            $merchantAccount = trim($this->_getConfigData('merchantAccount'));
            $requestData = Mage::getModel('adyen/adyen_data_idealPaymentRequest')
                    ->create($payment, $order->getGrandTotal(), $order, $payment, $merchantAccount);
            $response = $this->_service->beginIdealPayment(array('request' => $requestData));
            
            //debug || log
            Mage::getResourceModel('adyen/adyen_debug')->assignData($response);
            $this->_debugAdyen();
            Mage::log($requestData, self::DEBUG_LEVEL, "adyen_ideal.log", true);
            Mage::log($response, self::DEBUG_LEVEL, "adyen_ideal.log", true);
             
            if ($response) {
                return (string) $response->beginIdealPaymentResponse->response->returnUrl;
            }
        } catch (SoapFault $fault) {
            $this->writeLog("Adyen SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})");
            Mage::logException($fault);
            $this->_debugAdyen();
        }
        return false;
    }

}