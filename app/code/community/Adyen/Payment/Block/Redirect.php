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
 class Adyen_Payment_Block_Redirect extends Mage_Core_Block_Abstract {

    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getOrder() {
        if ($this->getOrder()) {
            return $this->getOrder();
        } elseif ($orderIncrementId == $this->_getCheckout()->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        } else {
            return null;
        }
    }

    protected function _toHtml() {
    	
    	$payment = $this->_getOrder()->getPayment()->getMethodInstance();
    	
    	$html = '<html><head><link href="http://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css"><link rel="stylesheet" type="text/css" href="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'/frontend/base/default/css/adyenstyle.css"><script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js" ></script></head><body class="redirect-body-adyen">';
    	// if pos payment redirect to app
    	if($payment->getCode() == "adyen_pos") {
    		
    		$adyFields = $payment->getFormFields();
    		// use the secure url (if not secure this will be filled in with http://
    		$url = urlencode(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)."adyen/process/successPos");
    		
    		// detect ios or android
    		$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
    		$android = stripos($ua,'android');

            // extra parameters so that you alway's return these paramters from the application
            $extra_paramaters = urlencode("/?originalCustomCurrency=".$adyFields['currencyCode']."&originalCustomAmount=".$adyFields['paymentAmount']. "&originalCustomMerchantReference=".$adyFields['merchantReference'] . "&originalCustomSessionId=".session_id());

            // add recurring before the callback url
            $recurring_parameters = "&recurringContract=".urlencode($adyFields['recurringContract'])."&shopperReference=".urlencode($adyFields['shopperReference']). "&shopperEmail=".urlencode($adyFields['shopperEmail']);

            // important url must be the latest parameter before extra parameters! otherwise extra parameters won't return in return url
            if($android !== false) { // && stripos($ua,'mobile') !== false) {
                // watch out some attributes are different from ios (sessionid and callback_automatic) added start_immediately
                $launchlink = "adyen://www.adyen.com/?sessionid=".date(U)."&amount=".$adyFields['paymentAmount']."&currency=".$adyFields['currencyCode']."&description=".$adyFields['merchantReference']. $recurring_parameters . "&start_immediately=1&callback_automatic=1&callback=".$url .$extra_paramaters;
            } else {
                //$launchlink = "adyen://payment?currency=".$adyFields['currencyCode']."&amount=".$adyFields['paymentAmount']."&description=".$adyFields['merchantReference']."&callback=".$url."&sessionId=".session_id()."&callbackAutomatic=1".$extra_paramaters;
                $launchlink = "adyen://payment?sessionId=".session_id()."&amount=".$adyFields['paymentAmount']."&currency=".$adyFields['currencyCode']."&description=".$adyFields['merchantReference']. $recurring_parameters . "&callbackAutomatic=1&callback=".$url .$extra_paramaters;
            }

            // log the launchlink
            Mage::log("Launchlink:".$launchlink, Zend_Log::DEBUG, "adyen_notification.log", true);

    		// call app directly without HPP
    		$html .= "<div id=\"pos-redirect-page\">
    					<div class=\"logo\"></div>
    					<div class=\"grey-header\">
    						<h1>POS Payment</h1>
    					</div>
    					<div class=\"amount-box\">".
    					$adyFields['paymentAmountGrandTotal'] .
    					"<a id=\"launchlink\" href=\"".$launchlink ."\" >Payment</a> ".
    					"</div>";

    		$html .= '<script type="text/javascript">
    				
    				function checkStatus() {
	    				$.ajax({
						    url: "'. $this->getUrl('adyen/process/getOrderStatus') . '",
						    type: "POST",
						    data: "merchantReference='.$adyFields['merchantReference'] .'",
						    success: function(data) {
						    	if(data == "true") {
						    		// redirect to success page
						    		window.location.href = "'. Mage::getBaseUrl()."adyen/process/success" . '";
						    	} else {
						    		window.location.href = "'. Mage::getBaseUrl()."adyen/process/cancel" . '";			
						    	}
						    }
						});
					}';
						    		
    				if($android !== false) {
    					$html .= 'url = document.getElementById(\'launchlink\').href;';
    					$html .= 'window.location.assign(url);';
    					$html .= 'window.onfocus = function(){setTimeout("checkStatus()", 500);};';
    				} else {
    					$html .= 'document.getElementById(\'launchlink\').click();';
    					$html .= 'setTimeout("checkStatus()", 5000);';
    				}
    				$html .= '</script></div>';
    	} else {
	        $form = new Varien_Data_Form();
	        $form->setAction($payment->getFormUrl())
	                ->setId($payment->getCode())
	                ->setName($payment->getFormName())
	                ->setMethod('POST')
	                ->setUseContainer(true);
	        foreach ($payment->getFormFields() as $field => $value) {
	            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
	        }
	        
	        $html.= $this->__(' ');
	        $html.= $form->toHtml();
	        $html.= '<script type="text/javascript">document.getElementById("'.$payment->getCode().'").submit();</script>';
    	}
    	$html.= '</body></html>';
        return $html;
    }

}
