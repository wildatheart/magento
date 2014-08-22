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
class Adyen_Payment_Model_Sales_Quote_Address_Total_PaymentFee extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_code = 'payment_fee';
    
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);
 
        $this->_setAmount(0);
        $this->_setBaseAmount(0);
 
        $quote = $address->getQuote();
		$val = Mage::Helper('adyen')->isPaymentFeeEnabled($quote);
        if ($address->getAllItems() && $val) {
            $currentAmount = $address->getPaymentFeeAmount();
            $fee = Mage::Helper('adyen')->getPaymentFeeAmount($quote);
            $balance = $fee - $currentAmount;
            
            $address->setPaymentFeeAmount($address->getQuote()->getStore()->convertPrice($balance));
            $address->setBasePaymentFeeAmount($balance);
                 
            $address->setGrandTotal($address->getGrandTotal() + $address->getPaymentFeeAmount());
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBasePaymentFeeAmount());
        }
        
        return $this;
    }
 
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amt = $address->getPaymentFeeAmount();
        
        if ($amt != 0) {
            $address->addTotal(array(
                    'code'=>$this->getCode(),
                    'title'=> Mage::Helper('checkout')->__("Afterpay fee"),
                    'value'=> $amt
            ));
        }
        
        return $this;
    }
}