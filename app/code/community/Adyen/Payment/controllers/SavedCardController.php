<?php

class Adyen_Payment_SavedCardController extends Mage_Core_Controller_Front_Action
{

    /**
     *
     * @var Mage_Customer_Model_Session
     */
    protected $_session = null;

    /**
     * Make sure customer is logged in and put it into registry
     */
    public function preDispatch()
    {
        parent::preDispatch();
        if (!$this->getRequest()->isDispatched()) {
            return;
        }
        $this->_session = Mage::getSingleton('customer/session');
        if (!$this->_session->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
        Mage::register('current_customer', $this->_session->getCustomer());
    }

    /**
     * Profiles listing
     */
    public function indexAction()
    {
        // check if there is a submit on card delete
        if ($this->getRequest()->isPost()) {
            $recurringDetailReference =  $this->getRequest()->getParam('recurringDetailReference');
            if($recurringDetailReference != "") {
                $storeId = Mage::app()->getStore()->getStoreId();
                $merchantAccount = Mage::getStoreConfig("payment/adyen_abstract/merchantAccount", $storeId);
                $customer = Mage::registry('current_customer');
                $shopperReference = $customer->getId();
                // do api call to delete this card
                $success = Mage::helper('adyen')->removeRecurringCart($merchantAccount, $shopperReference, $recurringDetailReference);

                // show result message
                if($success) {
                    $this->_getSession()->addSuccess(Mage::helper('adyen')->__('The card has been deleted.'));
                } else {
                    $this->_getSession()->addError(Mage::helper('adyen')->__('The card has not been deleted, please contact us.'));
                }
            }
        }

        $this->_title($this->__('Saved Cards'));
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $block = $this->getLayout()->getBlock('adyen.savedCards');
        if ($block) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $this->renderLayout();
    }

    /**
     * Retrieve customer session object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }


}