<?php
/**
 * @category    Alw
 * @package     Alw_Suppliers
 */
$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE sales_flat_order_item ADD COLUMN shipping_rate DECIMAL(12,4) NULL;
    ALTER TABLE sales_flat_invoice_item ADD COLUMN shipping_rate DECIMAL(12,4) NULL;
    ALTER TABLE sales_flat_shipment_item ADD COLUMN shipping_rate DECIMAL(12,4) NULL;
	ALTER TABLE sales_flat_creditmemo ADD COLUMN shipping_rate DECIMAL(12,4) NULL;  
	ALTER TABLE sales_flat_order_item ADD COLUMN base_shipping_rate DECIMAL(12,4) NULL;   
	ALTER TABLE sales_flat_invoice_item ADD COLUMN base_shipping_rate DECIMAL(12,4) NULL;   
	ALTER TABLE sales_flat_shipment_item ADD COLUMN base_shipping_rate DECIMAL(12,4) NULL;
	ALTER TABLE sales_flat_creditmemo ADD COLUMN base_shipping_rate DECIMAL(12,4) NULL;

	ALTER TABLE sales_flat_order_item ADD COLUMN supplier_id int(12) NULL;
	ALTER TABLE sales_flat_invoice_item ADD COLUMN supplier_id int(12) NULL;
    ALTER TABLE sales_flat_shipment_item ADD COLUMN supplier_id int(12) NULL;
	ALTER TABLE sales_flat_creditmemo_item ADD COLUMN supplier_id int(12) NULL;
	ALTER TABLE sales_flat_creditmemo ADD COLUMN supplier_id int(12) NULL;

	ALTER TABLE sales_flat_order_item ADD COLUMN commission DECIMAL(12,4) NULL;
	ALTER TABLE sales_flat_order_item ADD COLUMN base_commission DECIMAL(12,4) NULL;
    ALTER TABLE sales_flat_invoice_item ADD COLUMN commission DECIMAL(12,4) NULL;
    ALTER TABLE sales_flat_shipment_item ADD COLUMN commission DECIMAL(12,4) NULL;

");

$installer->endSetup();
?>