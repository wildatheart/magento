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

$installer->getConnection()
    ->addColumn($installer->getTable('adyen/event'),
        'success',
        array(
            'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
            'nullable' => true,
            'default' => null,
            'comment' => 'Action sucessfull'
        )
    );


$installer->endSetup();