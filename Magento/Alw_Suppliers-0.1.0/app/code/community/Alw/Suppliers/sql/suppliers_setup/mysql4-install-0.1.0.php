<?php
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();

if (!$installer->getConnection()->fetchOne("select * from {$this->getTable('admin_role')} where `role_name`='Supplier'")) {
    $installer->run("
        insert  into {$this->getTable('admin_role')} (`role_id` ,`parent_id` ,`tree_level` ,`sort_order` ,`role_type` ,`user_id` ,`role_name`) VALUES (NULL , '0', '1', '0', 'G', '0', 'Supplier');
    ");
    /**
     * Get the resource model
     */
    $resource = Mage::getSingleton('core/resource');
    /**
     * Retrieve the read connection
     */
    $readConnection = $resource->getConnection('core_read');
    $query = 'SELECT * FROM ' . $resource->getTableName('admin/role').' ORDER BY `admin_role`.`role_id` DESC LIMIT 1';
    /**
     * Execute the query and store the results in $results
     */
    $results = $readConnection->fetchAll($query);
    /**
     * Print out the results
     */
    foreach ($results as  $result)
    {
        $roleId = $result['role_id'];
        break;
    }
$supplierrole = array("admin/catalog", "admin/catalog/adminproducts", "admin/system", "admin/system/myaccount", "admin/catalog/myproducts", "admin/catalog/products", "admin/catalog/suggestcategories", "admin/dashboard", "admin/report", "admin/report/marketplace", "admin/report/marketplace/admincommission", "admin/sales", "admin/sales/creditmemo", "admin/sales/invoice", "admin/sales/order", "admin/sales/order/actions", "admin/sales/order/actions/cancel", "admin/sales/order/actions/create", "admin/sales/order/actions/creditmemo", "admin/sales/order/actions/hold", "admin/sales/order/actions/invoice", "admin/sales/order/actions/reorder", "admin/sales/order/actions/review_payment", "admin/sales/order/actions/ship", "admin/sales/order/actions/unhold", "admin/sales/order/actions/view", "admin/sales/shipment");
    foreach($supplierrole as $role){
        $installer->run("
        insert  into {$this->getTable('admin_rule')} (`rule_id`, `role_id`, `resource_id`, `privileges`, `assert_id`, `role_type`, `permission`) VALUES (NULL, '".$roleId."','".$role."', NULL, '0', 'G', 'allow'); ");
    }
 $supplierroledeny = array("admin/system/adminnotification", "admin/system/tools", "admin/system/api", "admin/system/design", "admin/system/currency", "admin/system/email_template", "admin/system/variable", "admin/system/order_statuses", "admin/system/cache", "admin/system/index", "admin/system/acl", "admin/system/store", "admin/system/config", "admin/system/convert" ,"admin/system/extensions", "admin/sales/transactions", "admin/sales/transactions/fetch", "admin/sales/recurring_profile", "admin/sales/billing_agreement", "admin/sales/tax","admin/sales/order/actions/email"," admin/sales/order/actions/edit", "admin/sales/order/actions/cancel","admin/sales/order/actions/capture", "admin/report/salesroot", "admin/report/shopcart", "admin/report/products", "admin/report/customers", "admin/report/review", "admin/report/tags", "admin/report/search", "admin/report/statistics", "admin/catalog/attributes", "admin/catalog/categories", "admin/catalog/tag", "admin/catalog/sitemap", "admin/catalog/requestcategories", "admin/catalog/search", "admin/catalog/urlrewrite", "admin/catalog/reviews_ratings", "admin/sales/checkoutagreement" );
foreach($supplierroledeny as $denyrole){
        $installer->run("
        insert  into {$this->getTable('admin_rule')} (`rule_id`, `role_id`, `resource_id`, `privileges`, `assert_id`, `role_type`, `permission`) VALUES (NULL, '".$roleId."','".$denyrole."', NULL, '0', 'G', 'deny'); ");
    }
    $installer->run("
        INSERT INTO {$this->getTable('core_config_data')}  (`config_id`, `scope`, `scope_id`, `path`, `value`) VALUES (NULL, 'default', '0', 'suppliers/suppliers/role_supplier_user_dropdown', ".$roleId."); ");
}    
$installer->endSetup();
?>