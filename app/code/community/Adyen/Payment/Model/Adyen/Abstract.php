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
abstract class Adyen_Payment_Model_Adyen_Abstract extends Mage_Payment_Model_Method_Abstract {

    /**
     * Zend_Log debug level
     * @var unknown_type
     */
    const DEBUG_LEVEL = 7;

    protected $_isGateway = false;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canRefundInvoicePartial = true;

    /**
     * TODO: whether a captured transaction may be voided by this gateway
     * This may happen when amount is captured, but not settled
     * @var bool
     */
    protected $_canCancelInvoice = true;

    /**
     * Magento Order Object
     * @var unknown_type
     */
    protected $_order;

    /**
     * Module identifiers
     */
    protected $_code = 'adyen_abstract';
    protected $_paymentMethod = 'abstract';

    /**
     * Internal objects and arrays for SOAP communication
     */
    protected $_service = NULL;
    protected $_accountData = NULL;

    /**
     * Payment Modification Request
     * @var unknown_type
     */
    protected $_paymentRequest = NULL;
    protected $_optionalData = NULL;
    protected $_testModificationUrl = 'https://pal-test.adyen.com/pal/adapter/httppost';
    protected $_liveModificationUrl = 'https://pal-live.adyen.com/pal/adapter/httppost';

    /**
     * @param Varien_Object $payment
     * @param unknown_type $amount
     */
    public function refund(Varien_Object $payment, $amount) {
        $this->writeLog('refund fx called');
        parent::refund($payment, $amount);
        return $this;
    }

    /**
     * In the backend it means Authorize only
     * @param Varien_Object $payment
     * @param unknown_type $amount
     */
    public function authorize(Varien_Object $payment, $amount) {
    	parent::authorize($payment, $amount);
        $payment->setLastTransId($this->getTransactionId())->setIsTransactionPending(true);
        if ($this->getCode() == 'adyen_boleto' || $this->getCode() == 'adyen_cc' || $this->getCode() == 'adyen_elv') {
            $_authorizeResponse = $this->_processRequest($payment, $amount, "authorise");
        }
        return $this;
    }

    /**
     * In backend it means Authorize && Capture
     * @param $payment
     * @param $amount
     */
    public function capture(Varien_Object $payment, $amount) {
        parent::capture($payment, $amount);
        $payment->setStatus(self::STATUS_APPROVED)
                ->setTransactionId($this->getTransactionId())
                ->setIsTransactionClosed(0);
        return $this;
    }
	
	public function authorise3d(Varien_Object $payment, $amount) {
        $authorizeResponse = $this->_processRequest($payment, $amount, "authorise3d");
		$responseCode = $authorizeResponse->paymentResult->resultCode;
		return $responseCode;
	}

    public function sendCaptureRequest(Varien_Object $payment, $amount, $pspReference) {
        if (empty($pspReference)) {
            $this->writeLog('oops empty pspReference');
            return $this;
        }
        $this->writeLog("sendCaptureRequest pspReference : $pspReference amount: $amount");
        return $this->_processRequest($payment, $amount, "capture", $pspReference);
    }

    public function sendRefundRequest(Varien_Object $payment, $amount, $pspReference) {
        if (empty($pspReference)) {
            $this->writeLog('oops empty pspReference');
            return $this;
        }
        $this->writeLog("sendRefundRequest pspReference : $pspReference amount: $amount");
        return $this->_processRequest($payment, $amount, "refund", $pspReference);
    }

    /**
     * Process the request here
     * @param Varien_Object $payment
     * @param unknown_type $amount
     * @param unknown_type $request
     * @param unknown_type $responseData
     */
    protected function _processRequest(Varien_Object $payment, $amount, $request, $pspReference = null) {
        $this->_initOrder();
        $this->_initService();
        $merchantAccount = trim($this->_getConfigData('merchantAccount'));
        $modificationResult = Mage::getModel('adyen/adyen_data_modificationResult');
        $requestData = Mage::getModel('adyen/adyen_data_modificationRequest')
                ->create($payment, $amount, $this->_order, $merchantAccount, $pspReference);
        
        switch ($request) {
            case "authorise":
                $requestData = Mage::getModel('adyen/adyen_data_paymentRequest')
                        ->create($payment, $amount, $this->_order, $this->_paymentMethod, $merchantAccount);

                $response = $this->_service->authorise(array('paymentRequest' => $requestData));
                break;
            case "authorise3d":
                $requestData = Mage::getModel('adyen/adyen_data_paymentRequest3d')
                        ->create($payment, $amount, $this->_order, $this->_paymentMethod, $merchantAccount);
				
                $response = $this->_service->authorise3d(array('paymentRequest3d' => $requestData));
                break;
            case "capture":
                $response = $this->_service->capture(array(
                    'modificationRequest' => $requestData,
                    'modificationResult' => $modificationResult));
                break;
            case "refund":
                $response = $this->_service->refund(array(
                    'modificationRequest' => $requestData,
                    'modificationResult' => $modificationResult));
                break;
        }

        if (!empty($response)) {
            $this->_processResponse($payment, $response, $request);
        }

        //debug || log
       	Mage::getResourceModel('adyen/adyen_debug')->assignData($response);
                $this->_debugAdyen();
                Mage::log($requestData, self::DEBUG_LEVEL, "$request.log", true);
                Mage::log("Response from Adyen:", self::DEBUG_LEVEL, "$request.log", true);
                Mage::log($response, self::DEBUG_LEVEL, "$request.log", true);

        //return $this;
        return $response;
    }

    /**
     * @desc authorise response
     * Process the response of the soap
     * @param Varien_Object $payment
     * @param unknown_type $response
     * @todo Add comment with checkout Authorised
     */
    protected function _processResponse(Varien_Object $payment, $response, $request = null) {
        if (!($response instanceof stdClass)) {
            return false;
        }
        switch ($request) {
            case "authorise":
            case "authorise3d":
                $responseCode = $response->paymentResult->resultCode;
                $pspReference = $response->paymentResult->pspReference;
                break;
            case "refund":
                $responseCode = $response->refundResult->response;
                $pspReference = $response->refundResult->pspReference;
                break;
            case "capture":
                $responseCode = $response->captureResult->response;
                $pspReference = $response->captureResult->pspReference;
                break;
            default:
                $this->writeLog("Unknown data type by Adyen");
                break;
        }
        switch ($responseCode) {

            case "RedirectShopper":
				$payment->setAdditionalInformation('paRequest', $response->paymentResult->paRequest);
				$payment->setAdditionalInformation('md', $response->paymentResult->md);
				$payment->setAdditionalInformation('issuerUrl', $response->paymentResult->issuerUrl);
				Mage::getSingleton('customer/session')->setRedirectUrl("adyen/process/validate3d");
				$this->_addStatusHistory($payment, $responseCode, $pspReference, $this->_getConfigData('order_status'));
                break;
            case "Refused":
                $errorMsg = Mage::helper('adyen')->__('The payment is REFUSED by Adyen.');
                Mage::throwException($errorMsg);
                break;
            case "Authorised":
                $this->_addStatusHistory($payment, $responseCode, $pspReference, $this->_getConfigData('order_status'));
                break;
            case "Received": // boleto payment
            	$additionalDataResult = $response->paymentResult->additionalData->entry;
            	$pdfUrl = $additionalDataResult[0]->value;
            	$this->_addStatusHistory($payment, $responseCode, $pspReference, false, $pdfUrl);
            	break;
            case '[capture-received]':
                $this->_addStatusHistory($payment, $responseCode, $pspReference);
                break;
            case '[refund-received]':
                $this->_addStatusHistory($payment, $responseCode, $pspReference);
                break;
            case "Error":
                $errorMsg = Mage::helper('adyen')->__('System error, please try again later');
                Mage::throwException($errorMsg);
                break;
            default:
                $this->writeLog("Unknown data type by Adyen");
                break;
        }

        //save all response data for a pure duplicate detection
        Mage::getModel('adyen/event')
                ->setPspReference($pspReference)
                ->setAdyenEventCode($responseCode)
                ->setAdyenEventResult($responseCode)
                ->setIncrementId($this->_order->getIncrementId())
                ->setPaymentMethod($this->getInfoInstance()->getCcType())
                ->setCreatedAt(now())
                ->saveData()
        ;
        return $this;
    }

    /**
     * @since 0.0.3
     * @param Varien_Object $payment
     * @param unknown_type $request
     * @param unknown_type $pspReference
     */
    protected function _addStatusHistory(Varien_Object $payment, $responseCode, $pspReference, $status = false, $boletoPDF = null) {
        
    	if($boletoPDF)
    		$payment->getOrder()->setAdyenBoletoPdf($boletoPDF);

        $comment = Mage::helper('adyen')->__('Adyen Result URL Notification(s): %s <br /> pspReference: %s', $responseCode, $pspReference);
        $payment->getOrder()->setAdyenEventCode($responseCode);
        $payment->getOrder()->addStatusHistoryComment($comment, $status);        
        $payment->setAdyenEventCode($responseCode);
        return $this;
    }

    /**
     * Format price
     * @param unknown_type $amount
     * @param unknown_type $format
     */
    protected function _formatAmount($amount, $format = 2) {
        return (int) number_format($amount, $format, '', '');
    }

    /**
     * @desc Get SOAP client
     * @return Adyen_Payment_Model_Adyen_Abstract 
     */
    protected function _initService() {
        $accountData = $this->getAccountData();
        $wsdl = $accountData['url']['wsdl'];
        $location = $accountData['url']['location'];
        $login = $accountData['login'];
        $password = $accountData['password'];
        $classmap = new Adyen_Payment_Model_Adyen_Data_Classmap();
        try {
            $this->_service = new SoapClient($wsdl, array(
                        'login' => $login,
                        'password' => $password,
                        'soap_version' => SOAP_1_1,
                        'style' => SOAP_DOCUMENT,
                        'encoding' => SOAP_LITERAL,
                        'location' => $location,
                        'trace' => 1,
                        'classmap' => $classmap));
        } catch (SoapFault $fault) {
            $this->writeLog("Adyen SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})");
            Mage::throwException(Mage::helper('adyen')->__('Can not connect payment service. Please try again later.'));
        }
        return $this;
    }

    /**
     * @desc soap urls
     * @return string 
     */
    protected function _getAdyenUrls() {
        $test = array(
            'location' => "https://pal-test.adyen.com/pal/servlet/soap/Payment",
            'wsdl' => Mage::getModuleDir('etc', 'Adyen_Payment') . DS . 'Payment.wsdl'
        );
        $live = array(
            'location' => "https://pal-live.adyen.com/pal/servlet/soap/Payment",
            'wsdl' => Mage::getModuleDir('etc', 'Adyen_Payment') . DS . 'Payment.wsdl'
        );
        if ($this->getConfigDataDemoMode()) {
            return $test;
        } else {
            return $live;
        }
    }

    /**
     * @desc Testing purposes only
     */
    protected function _debugAdyen() {
        $this->writeLog("Request Headers: ");
        $this->writeLog($this->_service->__getLastRequestHeaders());
        $this->writeLog("Request:");
        $this->writeLog($this->_service->__getLastRequest());
        $this->writeLog("Response Headers");
        $this->writeLog($this->_service->__getLastResponseHeaders());
        $this->writeLog("Response");
        $this->writeLog($this->_service->__getLastResponse());
    }

    /**
     * Adyen User Account Data
     */
    public function getAccountData() {
        $url = $this->_getAdyenUrls();
        $wsUsername = $this->getConfigDataWsUserName();
        $wsPassword = $this->getConfigDataWsPassword();
        $account = array(
            'url' => $url,
            'login' => $wsUsername,
            'password' => $wsPassword
        );
        return $account;
    }

    /**
     * @desc init order object
     * @return Adyen_Payment_Model_Adyen_Abstract 
     */
    protected function _initOrder() {
        if (!$this->_order) {
            $paymentInfo = $this->getInfoInstance();
            $this->_order = Mage::getModel('sales/order')
                    ->loadByIncrementId($paymentInfo->getOrder()->getRealOrderId());
        }
        return $this;
    }

    /**
     * Void payment
     *
     * @param   Varien_Object $invoicePayment
     * @return  Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment) {
        parent::void();
        $this->cancel($payment);
        return $this;
    }

    /**
     * @todo fix me validate()
     * @see Mage_Payment_Model_Method_Abstract::validate()
     */
    public function validate() {
        return $this;
    }

    /**
     * @desc Cancel order
     * @param Varien_Object $payment
     * @param type $amount
     * @return Adyen_Payment_Model_Adyen_Abstract 
     */
    public function cancel(Varien_Object $payment, $amount = null) {
        parent::cancel($payment);
        $this->writeLog("abstract -> cancel()" . get_class($this));
        return $this;
    }

    /**
     * @desc Adyen log fx
     * @param type $str
     * @return type 
     */
    public function writeLog($str) {
    	Mage::log($str, Zend_Log::DEBUG, "adyen_notification.log", true);
        return false;
    }

    /**
     * @status poor programming practises modification_result model not exist!
     * @param unknown_type $responseBody
     */
    public function getModificationResult($responseBody) {
        $result = new Varien_Object();
        $valArray = explode('&', $responseBody);
        foreach ($valArray as $val) {
            $valArray2 = explode('=', $val);
            $result->setData($valArray2[0], urldecode($valArray2[1]));
        }
        return $result;
    }

    public function getModificationUrl() {
        if ($this->getConfigDataDemoMode()) {
            return $this->_testModificationUrl;
        }
        return $this->_liveModificationUrl;
    }

    public function getConfigDataAutoCapture() {
        if (!$this->_getConfigData('auto_capture') || $this->_getConfigData('auto_capture') == 0) {
            return false;
        }
        return true;
    }

    public function getConfigDataAutoInvoice() {
        if (!$this->_getConfigData('auto_invoice') || $this->_getConfigData('auto_invoice') == 0) {
            return false;
        }
        return true;
    }

    public function getConfigDataAdyenCapture() {
        if ($this->_getConfigData('adyen_capture') && $this->_getConfigData('adyen_capture') == 1) {
            return true;
        }
        return false;
    }

    public function getConfigDataAdyenRefund() {
        if ($this->_getConfigData('adyen_refund') == 1) {
            return true;
        }
        return false;
    }

    /**
     * Return true if the method can be used at this time
     * @since 0.1.0.3r1
     * @return bool
     */
    public function isAvailable($quote=null) {
        if (!parent::isAvailable($quote)) {
            return false;
        }
        if (!is_null($quote)) {
            if ($this->_getConfigData('allowspecific', $this->_code)) {
                $country = $quote->getShippingAddress()->getCountry();
                $availableCountries = explode(',', $this->_getConfigData('specificcountry', $this->_code));
                if (!in_array($country, $availableCountries)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @desc Give Default settings
     * @example $this->_getConfigData('demoMode','adyen_abstract')
     * @since 0.0.2
     * @param string $code
     */
    protected function _getConfigData($code, $paymentMethodCode = null, $storeId = null) {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        if (empty($paymentMethodCode)) {
            return Mage::getStoreConfig("payment/adyen_abstract/$code", $storeId);
        }
        return Mage::getStoreConfig("payment/$paymentMethodCode/$code", $storeId);
    }

    /**
     * Used via Payment method.Notice via configuration ofcourse Y or N
     * @return boolean true on demo, else false
     */
    public function getConfigDataDemoMode() {
        if ($this->_getConfigData('demoMode') == 'Y') {
            return true;
        }
        return false;
    }

    public function getConfigDataWsUserName() {
        if ($this->getConfigDataDemoMode()) {
            return $this->_getConfigData('ws_username_test');
        }
        return $this->_getConfigData('ws_username_live');
    }

    public function getConfigDataWsPassword() {
        if ($this->getConfigDataDemoMode()) {
            return $this->_getConfigData('ws_password_test');
        }
        return $this->_getConfigData('ws_password_live');
    }

    /**
     * @since 0.0.2
     */
    public function getAvailableCCTypes() {
        $types = Mage::helper('adyen')->getCcTypes();
        $availableTypes = $this->_getConfigData('cctypes', 'adyen_cc');
        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
            foreach ($types as $code => $name) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                }
            }
        }
        return $types;
    }

    public function getAvailableBoletoTypes() {
    	$types = Mage::helper('adyen')->getBoletoTypes();
    	$availableTypes = $this->_getConfigData('boletotypes', 'adyen_boleto');
    	if ($availableTypes) {
    		$availableTypes = explode(',', $availableTypes);
    		foreach ($types as $code => $name) {
    			if (!in_array($code, $availableTypes)) {
    				unset($types[$code]);
    			}
    		}
    	}
    	return $types;
    }

    public function getConfigPaymentAction() {
        return Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE;
    }

    /**
     * @todo deprecates these use $this->_getConfigData($code,'paymentCode')
     * @desc No reason of doing this!!!
     */
    public function getConfigDataMerchantAccount() {
        return $this->_getConfigData('merchantAccount');
    }

    public function getConfigDataPaymentAuthorizedStatus() {
        return $this->_getConfigData('payment_authorized');
    }

    public function getConfigDataMailAuthorizedUpdate() {
        return $this->_getConfigData('mail_authorized');
    }

    public function getConfigDataCancelPendingCron() {
        return $this->_getConfigData('cancel_pending_cron');
    }

    public function getConfigDataHppSecretTest() {
        die("getConfigDataHppSecretTest in Adyen_Payment_Model_Adyen_Abstract");
        return $this->_getConfigData('secret_wordt');
    }

    public function getConfigDataHppSecretLive() {
        die("getConfigDataHppSecretLive in Adyen_Payment_Model_Adyen_Abstract");
        return $this->_getConfigData('secret_wordp');
    }

}
