<?php

$installer = $this;

/* @var $installer Adyen_Payment_Model_Entity_Setup */
$installer->startSetup();

$installer->getConnection()->addColumn($this->getTable('sales/quote_address'), 'payment_fee_amount', "decimal(12,4) null default null");
$installer->getConnection()->addColumn($this->getTable('sales/quote_address'), 'base_payment_fee_amount', "decimal(12,4) null default null");

$installer->getConnection()->addColumn($this->getTable('sales/order'), 'payment_fee_amount', "decimal(12,4) null default null");
$installer->getConnection()->addColumn($this->getTable('sales/order'), 'base_payment_fee_amount', "decimal(12,4) null default null");

$installer->getConnection()->addColumn($this->getTable('sales/invoice'), 'payment_fee_amount', "decimal(12,4) null default null");
$installer->getConnection()->addColumn($this->getTable('sales/invoice'), 'base_payment_fee_amount', "decimal(12,4) null default null");

$installer->getConnection()->addColumn($this->getTable('sales/creditmemo'), 'payment_fee_amount', "decimal(12,4) null default null");
$installer->getConnection()->addColumn($this->getTable('sales/creditmemo'), 'base_payment_fee_amount', "decimal(12,4) null default null");

$installer->getConnection()->addColumn($this->getTable('adyen/event'), 'success', "tinyint(1) null default null");

$installer->addAttribute('order_payment', 'adyen_klarna_number', array());

$installer->getConnection()->addColumn($this->getTable('sales/quote_address'), 'payment_installment_fee_amount', "decimal(12,4) null default null");
$installer->getConnection()->addColumn($this->getTable('sales/quote_address'), 'base_payment_installment_fee_amount', "decimal(12,4) null default null");

$installer->getConnection()->addColumn($this->getTable('sales/order'), 'payment_installment_fee_amount', "decimal(12,4) null default null");
$installer->getConnection()->addColumn($this->getTable('sales/order'), 'base_payment_installment_fee_amount', "decimal(12,4) null default null");

$installer->getConnection()->addColumn($this->getTable('sales/invoice'), 'payment_installment_fee_amount', "decimal(12,4) null default null");
$installer->getConnection()->addColumn($this->getTable('sales/invoice'), 'base_payment_installment_fee_amount', "decimal(12,4) null default null");

$installer->getConnection()->addColumn($this->getTable('sales/creditmemo'), 'payment_installment_fee_amount', "decimal(12,4) null default null");
$installer->getConnection()->addColumn($this->getTable('sales/creditmemo'), 'base_payment_installment_fee_amount', "decimal(12,4) null default null");

$installer->endSetup();