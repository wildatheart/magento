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
class Adyen_Payment_Model_Adyen_Cc extends Adyen_Payment_Model_Adyen_Abstract {

    protected $_code = 'adyen_cc';
    protected $_formBlockType = 'adyen/form_cc';
    protected $_infoBlockType = 'adyen/info_cc';
    protected $_paymentMethod = 'cc';
	
    /**
     * 1)Called everytime the adyen_cc is called or used in checkout
     * @description Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }        
        $info = $this->getInfoInstance();
        
        if ($this->isCseEnabled()) {
            $info->setAdditionalInformation('encrypted_data', $data->getEncryptedData());
        }
        else {
            $info->setCcType($data->getCcType())
                 ->setCcOwner($data->getCcOwner())
                 ->setCcLast4(substr($data->getCcNumber(), -4))
                 ->setCcNumber($data->getCcNumber())
                 ->setCcExpMonth($data->getCcExpMonth())
                 ->setCcExpYear($data->getCcExpYear())
                 ->setCcCid($data->getCcCid())
                 ->setPoNumber($data->getAdditionalData());
        }
        
        return $this;
    }
    
    public function getPossibleInstallments(){
        // retrieving quote
        $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();
        
        $amount = (double) $quote->getGrandTotal();
        $currency = $quote->getQuoteCurrencyCode();
        
        $max_installments = Mage::helper('adyen/installments')->getConfigValue($currency,$amount);
        
        $result = array();
        for($i=1;$i<=$max_installments;$i++){
            $partial_amount = ((double)$amount)/$i;
            $result[(string)$i] = $i."x ".$currency." ".number_format($partial_amount,2);
        }
    
        return $result;
    }
    
    /**
     * @desc Called just after asssign data
     */
    public function prepareSave() {
        parent::prepareSave();
    }
    
    /**
     * @desc Helper functions to get config data
     */
    public function isCseEnabled() {
        return Mage::getStoreConfig("payment/adyen_cc/cse_enabled");
    }
    public function getCsePublicKey() {
        return trim(Mage::getStoreConfig("payment/adyen_cc/cse_publickey"));
    }
	
    /**
     * @desc Specific functions for 3d secure validation
     */
	
	public function getOrderPlaceRedirectUrl() {
		$redirectUrl = Mage::getSingleton('customer/session')->getRedirectUrl();
		
		if (!empty($redirectUrl)) {
			Mage::getSingleton('customer/session')->unsRedirectUrl();
	        return Mage::getUrl($redirectUrl);
		}
		else {
			return parent::getOrderPlaceRedirectUrl();
		}
    }
	
	public function getFormUrl() {
		$this->_initOrder();
		$order = $this->_order;
		$payment = $order->getPayment();
		return $payment->getAdditionalInformation('issuerUrl');
	}
	
	public function getFormName() {
		return "Adyen CC";
	}
	
	public function getFormFields() {
		$this->_initOrder();
		$order = $this->_order;
		$payment = $order->getPayment();
		
		$adyFields = array();
		$adyFields['PaReq'] = $payment->getAdditionalInformation('paRequest');
		$adyFields['MD'] = $payment->getAdditionalInformation('md');
		$adyFields['TermUrl'] = Mage::getUrl('adyen/process/validate3d');
		
        return $adyFields;
	}
	
}
