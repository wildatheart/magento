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
class Adyen_Payment_Model_Sales_Quote_Address_Total_PaymentInstallmentFee extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_code = 'payment_installment_fee';

    /*
     *  keep in mind that collect is running twice if you save payment method.
     * First time before the payment step save and second time after payment step change
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        $quote = $address->getQuote();

        if ($address->getAllItems()) {
            $currentAmount = $address->getPaymentInstallmentFeeAmount();
            $payment = $quote->getPayment();

            if($payment && !empty($payment)) {

                $paymentMethod = $quote->getPayment()->getMethod() ;

                if($paymentMethod == "adyen_cc" || $paymentMethod == "adyen_oneclick" ) {

                    $info = $payment->getMethodInstance();

                    $instance = $info->getInfoInstance();
                    $numberOfInstallments = $instance->getAdditionalInformation('number_of_installments');

                    if($numberOfInstallments > 0)
                    {
                        // get the Interest Rate of this installment

                        // get cc type
                        $ccType = $instance->getCcType();

                        // get installment for this specific card type
                        $ccTypeInstallments = "installments_".$ccType;

                        $all_installments = Mage::helper('adyen/installments')->getInstallments(null, $ccTypeInstallments);
                        if(empty($all_installments)) {
                            // use default installments
                            $all_installments = Mage::helper('adyen/installments')->getInstallments();
                        }

                        $installmentKey = $numberOfInstallments - 1;

                        $installment = $all_installments[$installmentKey];

                        if($installment != null && is_array($installment)) {

                            // check if interest rate is filled in
                            if(isset($installment[3]) && $installment[3] > 0) {

                                $this->_setAmount(0);
                                $this->_setBaseAmount(0);

                                $interestRate = $installment[3];
                                $grandTotal = $address->getGrandTotal();
                                $fee = ($grandTotal / 100) * $interestRate;

                                $balance = $fee - $currentAmount;

                                $address->setPaymentInstallmentFeeAmount($address->getQuote()->getStore()->convertPrice($balance));
                                $address->setBasePaymentInstallmentFeeAmount($balance);

                                $address->setGrandTotal($address->getGrandTotal() + $address->getPaymentInstallmentFeeAmount());
                                $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getBasePaymentInstallmentFeeAmount());
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amt = $address->getPaymentInstallmentFeeAmount();

        if ($amt != 0) {
            $address->addTotal(array(
                'code'=>$this->getCode(),
                'title'=> Mage::helper('adyen')->__('Installment Fee'),
                'value'=> $amt
            ));
        }

        return $this;
    }
}