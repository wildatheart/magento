<?php
/**
 * Magento
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
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_CatalogInventory
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Installments manipulation helper
 */
class Adyen_Payment_Helper_Installments
{
    /**
     * Retrieve fixed qty value
     *
     * @param mixed $qty
     * @return float|null
     */
    protected function _fixQty($qty)
    {
        return (!empty($qty) ? (float)$qty : null);
    }

    /**
     * Generate a storable representation of a value
     *
     * @param mixed $value
     * @return string
     */
    protected function _serializeValue($value)
    {
    	return serialize($value);
    }

    /**
     * Create a value from a storable representation
     *
     * @param mixed $value
     * @return array
     */
    protected function _unserializeValue($value)
    {
        if (is_string($value) && !empty($value)) {
            return unserialize($value);
        } else {
            return array();
        }
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param mixed
     * @return bool
     */
    protected function _isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $_id => $row) {
            if (!is_array($row) || !array_key_exists('installment_currency',$row) || !array_key_exists('installment_boundary', $row) || !array_key_exists('installment_frequency', $row)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Encode value to be used in Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     * deserialized DB entry => HTML form
     * @param array
     * @return array
     */
    protected function _encodeArrayFieldValue(array $value)
    {
        $result = array();
        foreach ($value as $triplet){
        	list($currency,$boundary,$frequency) = $triplet; 
            $_id = Mage::helper('core')->uniqHash('_');
            $result[$_id] = array(
            	'installment_currency' => $currency,
                'installment_boundary' => $boundary,
                'installment_frequency' => $frequency,
            );
        }
        return $result;
    }

    /**
     * Decode value from used in Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     * HTML form => deserialized DB entry
     * @param array
     * @return array
     */
    protected function _decodeArrayFieldValue(array $value)
    {
        $result = array();
        unset($value['__empty']);
        foreach ($value as $_id => $row) {
            if (!is_array($row) || !array_key_exists('installment_currency',$row) || !array_key_exists('installment_boundary', $row) || !array_key_exists('installment_frequency', $row)) {
                continue;
            }
            $currency = $row['installment_currency'];
            $boundary = $row['installment_boundary'];
            $frequency = $row['installment_frequency'];
            $result[] = array($currency,$boundary,$frequency);
        }
        return $result;
    }

    /**
     * Retrieve maximum number for installments for given amount with config
     *
     * @param int $customerGroupId
     * @param mixed $store
     * @return float|null
     */
    public function getConfigValue($curr,$amount, $store = null)
    {
        $value = Mage::getStoreConfig("payment/adyen_cc/installments", $store);
        $value = $this->_unserializeValue($value);
        if ($this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_decodeArrayFieldValue($value);
        }
        $cur_minimal_boundary = -1;
        $resulting_freq = 1;
        foreach ($value as $row) {
        	list($currency,$boundary,$frequency) = $row;
            if ($curr == $currency){
            	if($amount <= $boundary && ($boundary <= $cur_minimal_boundary || $cur_minimal_boundary == -1) ) {
	            	$cur_minimal_boundary = $boundary;
	            	$resulting_freq = $frequency;
	            }
	            if($boundary == "" && $cur_minimal_boundary == -1){
	            	$resulting_freq = $frequency;
	            }
            }
           
        }
        return $resulting_freq;
    }
    
    public function isInstallmentsEnabled($store = null){
    	$value = Mage::getStoreConfig("payment/adyen_cc/enable_installments", $store);
    	return $value;
    }

    /**
     * Make value readable by Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     *
     * @param mixed $value
     * @return array
     */
    public function makeArrayFieldValue($value)
    {
        $value = $this->_unserializeValue($value);
        if (!$this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_encodeArrayFieldValue($value);
        }
        return $value;
    }

    /**
     * Make value ready for store
     *
     * @param mixed $value
     * @return string
     */
    public function makeStorableArrayFieldValue($value)
    {
        if ($this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_decodeArrayFieldValue($value);
        }
        $value = $this->_serializeValue($value);
        return $value;
    }
}
