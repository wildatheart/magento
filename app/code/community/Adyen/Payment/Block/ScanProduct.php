<?php

class Adyen_Payment_Block_ScanProduct extends Mage_Core_Block_Template
{

    public function hasEnableScanner()
    {
        return (string) Mage::helper('adyen')->hasEnableScanner();
    }

}