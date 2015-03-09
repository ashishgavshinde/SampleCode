<?php
/**
 * @category    Alw
 * @package     Alw_Suppliers
 * @Description Sql file is used to alter admin/user table to add supplier address information
 */
$installer=$this;
$installer->startSetup();
$installer->run("
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD `company` varchar(255) default NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD UNIQUE (`company`);
		ALTER TABLE  `".$this->getTable('admin/user')."` ADD `supplier_createdat` timestamp NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `company_logo` varchar(255) default NULL;
		ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `commission` DECIMAL( 10, 2 ) NOT NULL default '0';
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `company_detail` varchar(255) default NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `facebook_link` varchar(255) default NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `twitter_link` varchar(255) default NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `term_and_condition` varchar(255) NOT NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `address` varchar(255) NOT NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `city` varchar(100) default NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `state` varchar(50)  NOT NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `country` varchar(50)  default NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `zip_code` varchar(100)  default NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `mobile` varchar(100) default NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `fax` varchar(100) default NULL;
        ALTER TABLE  `".$this->getTable('admin/user')."` ADD  `paypal_account_id` varchar(100) default NULL;
    ");
    /* Ass created by attribute for supplier */
    $catalogInstaller = Mage::getResourceModel('catalog/setup', 'catalog_setup');
    $catalogInstaller->addAttribute('catalog_product', 'created_by', array(
        'type'     => 'int',
        'visible'  => false,
        'required' => false
    ));
    
    $resource = Mage::getSingleton('core/resource');
    $read = $resource->getConnection('core_read');
    $write = $resource->getConnection('core_write');
    $write->beginTransaction();
    
    $query = 'SELECT `attribute_id` FROM ' . $catalogInstaller->getTable('eav_attribute').' ORDER BY `attribute_id` DESC LIMIT 1';
    /**
     * Execute the query and store the results in $results
     */
    $results = $read->fetchAll($query);
    /**
     * Print out the results
     */
    foreach ($results as  $result)
    {
        $attributeId = $result['attribute_id'];
        break;
    }
    /* Set all product "created_by" attribute  value to 1 */
    $collection = Mage::getModel('catalog/product')->getCollection();
    foreach ($collection as $_product) {
       $write->insert($catalogInstaller->getTable("catalog_product_entity_int"),array("entity_type_id"=>4,"attribute_id"=>$attributeId,"entity_id"=>$_product->getId(),"value"=>1));
       $write->commit();
    }
$installer->endSetup();