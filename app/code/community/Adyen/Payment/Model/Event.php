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
class Adyen_Payment_Model_Event extends Mage_Core_Model_Abstract {

    const ADYEN_EVENT_AUTHORISATION = 'AUTHORISATION';
    const ADYEN_EVENT_PENDING = 'PENDING';
    const ADYEN_EVENT_AUTHORISED = 'AUTHORISED';
    const ADYEN_EVENT_CANCELLED = 'CANCELLED';
    const ADYEN_EVENT_REFUSED = 'REFUSED';
    const ADYEN_EVENT_ERROR = 'ERROR';
    const ADYEN_EVENT_REFUND = 'REFUND';
    const ADYEN_EVENT_CAPTURE = 'CAPTURE';
    const ADYEN_EVENT_CANCELLATION = 'CANCELLATION';
    const ADYEN_EVENT_POSAPPROVED = 'POS_APPROVED';
    const ADYEN_EVENT_HANDLEDEXTERNALLY  = 'HANDLED_EXTERNALLY';
    
    /**
     * Initialize resources
     */
    protected function _construct() {
        $this->_init('adyen/adyen_event');
    }

    /**
     * Check if the Adyen Notification is already stored in the system
     * @param type $dbPspReference
     * @param type $dbEventCode
     * @return boolean true if the event is a duplicate
     */
    public function isDuplicate($pspReference, $event) {
        $result = $this->getResource()->getEvent(trim($pspReference), trim($event));
        return (empty($result)) ? false : true;
    }

    public function getEvent($pspReference, $event) {
        return $this->getResource()->getEvent($pspReference, $event);
    }

    public function saveData($updateAdyenStatus = true) {
    	
        $this->getResource()->saveData($this);
        if($updateAdyenStatus)
        	$this->updateAdyenStatus();
    }

    /**
     * Update sales grid && sales flat order
     * @since 0.1.0.9v
     * @param type $response
     */
    public function updateAdyenStatus() {
        $incrementId = $this->getIncrementId();
        $eventData = $this->getAdyenEventResult();
        if (!empty($incrementId) && !(empty($eventData)))
            $this->getResource()->updateAdyenStatus($incrementId, $eventData);
    }

    public function getOriginalPspReference($incrementId) {
        $originalReference = $this->getResource()->getOriginalPspReference($incrementId);
        return (!empty($originalReference)) ? $originalReference['psp_reference'] : false;
    }

    /**
     * Detect Bank Transfer
     * @param type $incrementId
     * @param type $adyenEventCode
     * @return boolean true if bankTrasnfer
     */
    public function isBanktransfer($incrementId, $adyenEventCode = 'PENDING') {
        $_incrementId = trim($incrementId);
        $_adyenEventCode = trim($adyenEventCode);
        $pendingEvent = $this->getResource()->getBanktransferEvent($_incrementId, $_adyenEventCode);
        if (empty($pendingEvent))
            return false;
        if (!isset($pendingEvent['payment_method']))
            return false;
        return strpos($pendingEvent['payment_method'], 'bankTransfer') !== false ? true : false;
    }

}