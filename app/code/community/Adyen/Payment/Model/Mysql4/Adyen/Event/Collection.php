<?php

class Adyen_Payment_Model_Mysql4_Adyen_Event_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('adyen/adyen_event');
        $this->setItemObjectClass('adyen/event');
    }
}