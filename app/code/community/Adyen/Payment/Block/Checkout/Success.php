<?php 

class Adyen_Payment_Block_Checkout_Success extends Mage_Checkout_Block_Onepage_Success
{
	private $order;
	
	
	/*
	 * check if payment method is boleto
	 */
	public function isBoletoPayment()
	{
		$this->order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
		
		if ($this->order->getPayment()->getMethod() == "adyen_boleto")
			return true;
		
		return false;
	}
	
	/*
	 * get the boleto pdf url from order
	 */
	public function getUrlBoletoPDF()
	{
		$result = "";
		
		// if isBoletoPayment is not called first load the order
		if($this->order == null)
			$this->order = Mage::getModel('sales/order')->loadByIncrementId($this->getOrderId());
				
		if ($this->order->getPayment()->getMethod() == "adyen_boleto")
			$result = $this->order->getAdyenBoletoPdf();
				
		return $result;
	}
		
}

?>