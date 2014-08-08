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
class Adyen_Payment_Model_Adyen_Oneclick extends Adyen_Payment_Model_Adyen_Cc {

    protected $_code = 'adyen_oneclick';
    protected $_formBlockType = 'adyen/form_oneclick';
    protected $_infoBlockType = 'adyen/info_oneclick';
    protected $_paymentMethod = 'oneclick';
    protected $_canUseInternal = false; // not possible through backoffice interface


    /*
     * only enable if adyen_cc is enabled
     */
    public function isAvailable($quote = null)
    {
        $isAvailable = parent::isAvailable($quote);

        // check if adyen_cc is enabled if not disable oneclick as well
        $isCCActive = $this->_getConfigData('active', 'adyen_cc');
        if(!$isCCActive)
            return false;

        return $isAvailable;
    }
    public function assignData($data) {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();

        // check if selected payment is a recurring payment
        if($data->getRecurring() != "") {

            // get the selected recurring card
            $recurringSelectedKey =  $data->getRecurring();

            // get cvc code for this creditcard
            $recurringDetailReferenceKey = "recurringDetailReference_".$recurringSelectedKey;
            $cvcKey = "oneclick_cid_".$recurringSelectedKey;

            // don't use magic getter but get the key because this is a variable value
            $recurringDetailReference = $data->getData($recurringDetailReferenceKey);
            //$cvc = $data->getData($cvcKey);

            // save information as additional information so you don't have to add column in table
            $info->setAdditionalInformation('recurring_detail_reference', $recurringDetailReference);

            if ($this->isCseEnabled()) {
                $info->setAdditionalInformation('encrypted_data', $data->getEncryptedDataOneclick());
            }
            else {

                if($data->getRecurring() != "") {

                    // check if expiry month and year is changed
                    $expiryMonth = $data->getData("oneclick_exp_month" . $recurringSelectedKey);
                    $expiryYear = $data->getData("oneclick_exp_year_" . $recurringSelectedKey);

                    // just set default data for info block only
                    $info->setCcType($data->getData("oneclick_type_" . $recurringSelectedKey))
                        ->setCcOwner($data->getData("oneclick_owner_" . $recurringSelectedKey))
                        ->setCcLast4($data->getData("oneclick_last_4_" . $recurringSelectedKey))
                        ->setCcExpMonth($data->getData("oneclick_exp_month_" . $recurringSelectedKey))
                        ->setCcExpYear($data->getData("oneclick_exp_year_" . $recurringSelectedKey))
                        ->setCcCid($data->getData("oneclick_cid_" . $recurringSelectedKey));
                }
            }
        } else {
            Mage::throwException(Mage::helper('adyen')->__('Payment Method is complusory in order to process your payment'));
        }
        return $this;
    }

    public function getlistRecurringDetails()
    {
        $quote = (Mage::getModel('checkout/type_onepage') !== false)? Mage::getModel('checkout/type_onepage')->getQuote(): Mage::getModel('checkout/session')->getQuote();
        $customerId = $quote->getCustomerId();
        return $this->_processRecurringRequest($customerId);
    }

}