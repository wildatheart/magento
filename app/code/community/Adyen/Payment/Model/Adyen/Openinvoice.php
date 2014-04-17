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
class Adyen_Payment_Model_Adyen_Openinvoice extends Adyen_Payment_Model_Adyen_Hpp {

	protected $_canUseInternal = false;
    protected $_code = 'adyen_openinvoice';
    protected $_formBlockType = 'adyen/form_openinvoice';
    protected $_infoBlockType = 'adyen/info_openinvoice';
    protected $_paymentMethod = 'openinvoice';

    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->setCcType('openinvoice');
        return $this;
    }

    /**
     * @desc Get url of Adyen payment
     * @return string 
     * @todo add brandCode here
     */
    public function getFormUrl() {
    	$paymentRoutine = $this->_getConfigData('payment_routines', 'adyen_hpp');
    	$openinvoiceType = $this->_getConfigData('openinvoicetypes', 'adyen_openinvoice');

        switch ($this->getConfigDataDemoMode()) {
            case true:
            	if ($paymentRoutine == 'single' && empty($openinvoiceType)) {
                    $url = 'https://test.adyen.com/hpp/pay.shtml';
                } else {
                    $url = "https://test.adyen.com/hpp/details.shtml?brandCode=".$openinvoiceType;
                }
                break;
            default:
            	if ($paymentRoutine == 'single' && empty($openinvoiceType)) {
                    $url = 'https://live.adyen.com/hpp/pay.shtml';
                } else {
                    $url = "https://live.adyen.com/hpp/details.shtml?brandCode=".$openinvoiceType;
                }
                break;
        }
        return $url;
    }
    
    public function getFormName() {
    	return "Adyen HPP";
    }

    /**
     * @desc Openinvoice Optional Fields.
     * @desc Notice these are used to prepopulate the fields, but client can edit them at Adyen.
     * @return type array
     */
    public function getFormFields() {
        $adyFields = parent::getFormFields();
        $adyFields = $this->getOptionalFormFields($adyFields,$this->_order);
        return $adyFields;
    }
    
    public function getOptionalFormFields($adyFields,$order) {
        if (empty($order)) return $adyFields;
        $billingAddress = $order->getBillingAddress();
        $adyFields['shopper.firstName'] = $billingAddress->getFirstname();
        $adyFields['shopper.lastName'] = $billingAddress->getLastname();
        $adyFields['billingAddress.street'] = $this->getStreet($billingAddress)->getName();
        $adyFields['billingAddress.houseNumberOrName'] = $this->getStreet($billingAddress)->getHouseNumber();
        $adyFields['billingAddress.city'] = $billingAddress->getCity();
        $adyFields['billingAddress.postalCode'] = $billingAddress->getPostcode();
        $adyFields['billingAddress.stateOrProvince'] = $billingAddress->getRegion();
        $adyFields['billingAddress.country'] = $billingAddress->getCountryId();
        $sign = $adyFields['billingAddress.street'] .
                $adyFields['billingAddress.houseNumberOrName'] .
                $adyFields['billingAddress.city'] .
                $adyFields['billingAddress.postalCode'] .
                $adyFields['billingAddress.stateOrProvince'] .
                $adyFields['billingAddress.country']
        ;
        //Generate HMAC encrypted merchant signature
        $secretWord = $this->_getSecretWord();
        $signMac = Zend_Crypt_Hmac::compute($secretWord, 'sha1', $sign);
        $adyFields['billingAddressSig'] = base64_encode(pack('H*', $signMac));

        
        $deliveryAddress = $order->getShippingAddress();
        if($deliveryAddress != null)
        {
	        $adyFields['deliveryAddress.street'] = $this->getStreet($deliveryAddress)->getName();
	        $adyFields['deliveryAddress.houseNumberOrName'] = $this->getStreet($deliveryAddress)->getHouseNumber();
	        $adyFields['deliveryAddress.city'] = $deliveryAddress->getCity();
	        $adyFields['deliveryAddress.postalCode'] = $deliveryAddress->getPostcode();
	        $adyFields['deliveryAddress.stateOrProvince'] = $deliveryAddress->getRegion();
	        $adyFields['deliveryAddress.country'] = $deliveryAddress->getCountryId();
	        $sign = $adyFields['deliveryAddress.street'] .
		        $adyFields['deliveryAddress.houseNumberOrName'] .
		        $adyFields['deliveryAddress.city'] .
		        $adyFields['deliveryAddress.postalCode'] .
		        $adyFields['deliveryAddress.stateOrProvince'] .
		        $adyFields['deliveryAddress.country']
	        ;
	        //Generate HMAC encrypted merchant signature
	        $secretWord = $this->_getSecretWord();
	        $signMac = Zend_Crypt_Hmac::compute($secretWord, 'sha1', $sign);
	        $adyFields['deliveryAddressSig'] = base64_encode(pack('H*', $signMac));
   	 	}
        
        
        if ($adyFields['shopperReference'] != self::GUEST_ID) {
            $customer = Mage::getModel('customer/customer')->load($adyFields['shopperReference']);
            $adyFields['shopper.gender'] = strtoupper($this->getCustomerAttributeText($customer, 'gender'));
            $adyFields['shopper.infix'] = $customer->getPrefix();
            $dob = $customer->getDob();
            if (!empty($dob)) {
                $adyFields['shopper.dateOfBirthDayOfMonth'] = $this->getDate($dob, 'd');
                $adyFields['shopper.dateOfBirthMonth'] = $this->getDate($dob, 'm');
                $adyFields['shopper.dateOfBirthYear'] = $this->getDate($dob, 'Y');
            }
        }
        $adyFields['shopper.telephoneNumber'] = $billingAddress->getTelephone();
        
        
        $count = 0;
        $currency = $order->getOrderCurrencyCode();
        $additional_data_sign = array();
        
        foreach ($order->getItemsCollection() as $item) {
        	//skip dummies
        	if ($item->isDummy()) continue;
        	
        	++$count;
        	$linename = "line".$count;
        	$additional_data_sign['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
        	$additional_data_sign['openinvoicedata.' . $linename . '.description'] = $item->getName();
        	$additional_data_sign['openinvoicedata.' . $linename . '.itemAmount'] = $this->_formatAmount($item->getPrice());
	      	$additional_data_sign['openinvoicedata.' . $linename . '.itemVatAmount'] =  $this->_formatAmount(($item->getTaxAmount()>0 && $item->getPriceInclTax()>0)?$item->getPriceInclTax() - $item->getPrice():$item->getTaxAmount());
        	$additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = (int) $item->getQtyOrdered();
        	$additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "None";
        }
        
        //discount cost
        $linename = "line".++$count;
        $additional_data_sign['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
        $additional_data_sign['openinvoicedata.' . $linename . '.description'] = Mage::helper('adyen')->__('Total Discount');
        $additional_data_sign['openinvoicedata.' . $linename . '.itemAmount'] = $this->_formatAmount($order->getDiscountAmount());
        $additional_data_sign['openinvoicedata.' . $linename . '.itemVatAmount'] = "000";
        $additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = 1;
        $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "None";
        
        //shipping cost
        $linename = "line".++$count;
        $additional_data_sign['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
        $additional_data_sign['openinvoicedata.' . $linename . '.description'] = $order->getShippingDescription();
        $additional_data_sign['openinvoicedata.' . $linename . '.itemAmount'] = $this->_formatAmount($order->getShippingAmount());
        $additional_data_sign['openinvoicedata.' . $linename . '.itemVatAmount'] = $this->_formatAmount($order->getShippingTaxAmount());
        $additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = 1;
        $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "None";
        
        //tax costs
        $linename = "line".++$count;
        $additional_data_sign['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
        $additional_data_sign['openinvoicedata.' . $linename . '.description'] = Mage::helper('adyen')->__('Tax');
        $additional_data_sign['openinvoicedata.' . $linename . '.itemAmount'] = $this->_formatAmount($order->getTaxAmount());
        $additional_data_sign['openinvoicedata.' . $linename . '.itemVatAmount'] = "000";
        $additional_data_sign['openinvoicedata.' . $linename . '.numberOfItems'] = 1;
        $additional_data_sign['openinvoicedata.' . $linename . '.vatCategory'] = "None";
        
        // general for invoicelines
        $additional_data_sign['openinvoicedata.refundDescription'] = "Refund / Correction for ".$adyFields['merchantReference'];
        $additional_data_sign['openinvoicedata.numberOfLines'] = $count;
       
        // add merchantsignature in additional signature
        $additional_data_sign['merchantSig'] = $adyFields['merchantSig'];
        
        // generate signature
        ksort($additional_data_sign);

        // signature is first alphabatical keys seperate by : and then | and then the values seperate by :
        $sign_additional_data_keys = "";
        $sign_additional_data_values = "";
        foreach($additional_data_sign as $key => $value) {
        	
        	// add to fields
        	$adyFields[$key] = $value;
        	
        	// create sign
        	$sign_additional_data_keys .= $key;
        	$sign_additional_data_values .= $value;
        	
			$keys = array_keys($additional_data_sign);
        	if(end($keys) != $key) {
        		$sign_additional_data_keys .= ":";
        		$sign_additional_data_values .= ":";
        	}
        }
        
        $sign_additional_data =  $sign_additional_data_keys . "|" . $sign_additional_data_values;
       
        $secretWord = $this->_getSecretWord();
        $signMac = Zend_Crypt_Hmac::compute($secretWord, 'sha1', $sign_additional_data);
        $adyFields['openinvoicedata.sig'] =  base64_encode(pack('H*', $signMac));
        
        
        Mage::log($adyFields, self::DEBUG_LEVEL, 'http-request.log');
        
        return $adyFields;
    }    

    /**
     * Get Attribute label
     * @param type $customer
     * @param type $code
     * @return type 
     */
    public function getCustomerAttributeText($customer, $code='gender') {
        $helper = Mage::helper('adyen');
        return $helper->htmlEscape($customer->getResource()->getAttribute($code)->getSource()->getOptionText($customer->getGender()));
    }

    /**
     * Date Manipulation
     * @param type $date
     * @param type $format
     * @return type date
     */
    public function getDate($date = null, $format = 'Y-m-d H:i:s') {
        if (strlen($date) < 0) {
            $date = date('d-m-Y H:i:s');
        }
        $timeStamp = new DateTime($date);
        return $timeStamp->format($format);
    }
    
    /** 
     * Street format
     * @param type $address
     * @return Varien_Object 
     */
    public function getStreet($address) {
        if (empty($address)) return false;
        $street = self::formatStreet($address->getStreet());
        $streetName = $street['0'];
        unset($street['0']);
        $streetNr = implode('',$street);
        return new Varien_Object(array('name' => $streetName, 'house_number' => $streetNr));
    }
    
    /**
     * Fix this one string street + number
     * @example street + number
     * @param type $street
     * @return type $street
     */
    static public function formatStreet($street) {
        if (count($street) != 1) {
            return $street;
        }        
        preg_match('/((\s\d{0,10})|(\s\d{0,10}\w{1,3}))$/i', $street['0'], $houseNumber, PREG_OFFSET_CAPTURE);
        if(!empty($houseNumber['0'])) {
           $_houseNumber = trim($houseNumber['0']['0']);
           $position = $houseNumber['0']['1'];
           $streeName = trim(substr($street['0'], 0, $position));
           $street = array($streeName,$_houseNumber);
        }
        return $street;
    }
}