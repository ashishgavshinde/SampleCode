<?php
/**
 * @category    Alw
 * @package     Alw_Suppliers
 * Model to get supplier user list, send email notification etc.
 */
class Alw_Suppliers_Model_Suppliers extends Mage_Core_Model_Abstract
{
    /* 
     * Constructor class 
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('suppliers/suppliers');
    }

    /* 
     * Function to get role id of the supplier 
     */ 
    public function getSuppliers()
    {
        $roleId = '';
        $roleCollection = Mage::getModel('admin/roles')->getCollection();
        $roleId = Mage::getStoreConfig('suppliers/suppliers/role_supplier_user_dropdown');
        $roleCollection->addFieldToFilter('role_id',array('like'=> $roleId));
        foreach ($roleCollection as $role) {
            $roleId = $role['role_id'];
        }
        return $roleId;
    }
    /* 
     * Get list of all user with role name select in the system configuration 
     */
    public function getSupplierUser()
    {
        $roleId = $this->getSuppliers();
        $adminRole = Mage::getSingleton('core/resource')->getTableName('admin_role');
        $adminUser = Mage::getSingleton('core/resource')->getTableName('admin_user');
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write'); //Database connection
        /* Get list of all admin user */
        $sql = "SELECT`ce`.user_id, `ce`.company FROM `".$adminRole."` AS `main_table` INNER JOIN `".$adminUser."` AS `ce` ON main_table.user_id = ce.user_id WHERE (ce.is_active=1 and main_table.parent_id=".$roleId.");";
        $userCollection = $connection->query($sql);
        $optionArray = array();
        $i=0;
        foreach($userCollection as $user){
            $optionArray[$i]['value'] = $user['user_id'];
            $optionArray[$i]['label'] = $user['company'];
            $i++;    
        }
        $this->_options = $optionArray;
        return $this->_options;
    }
    /* 
     * Function to add uniqueness in company exist 
     */ 
    public function companyExists($companyName, $userId) 
    {
        $adminUser = Mage::getSingleton('core/resource')->getTableName('admin_user');
        $read = Mage::getSingleton('core/resource')->getConnection('core_write'); 
        if ($userId) {
            $select = "SELECT `user_id` FROM `".$adminUser."` WHERE (company= '".$companyName."' and user_id <> '".$userId."');";    
        } else {
            $select = "SELECT `user_id` FROM `".$adminUser."` WHERE (company= '".$companyName."');";
        }
        try {
            $result = $read->fetchRow($select);
            return (is_array($result) && count($result) > 0) ? 1 : 0;
        } catch (Exception $e)    {
            return $e->getMessage();
        }
    }
    /* 
     * Send Email
     */
    public function sendEmail($firstName,$lastName,$email,$emailTemplate,$status, $comment='')
    {
        /* Create an array of variables to assign to template */
        $emailTemplateVariables = array();    
        $emailTemplateVariables['rec_first_name'] = ucwords($firstName);
        $emailTemplateVariables['rec_last_name'] = ucwords($lastName);
        $emailTemplateVariables['rec_cancel_comment'] =$comment;
        $emailTemplateVariables['sender_name'] = Mage::getStoreConfig('trans_email/ident_general/name');
        $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name'));
        $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
        $emailTemplate->setTemplateSubject($status);
        try {
            $emailTemplate->send($email,$firstName, $emailTemplateVariables);
        } catch (Mage_Core_Exception $e) {
            $this->_redirectError(Mage::getUrl('*/*/index', array('_secure' => true)));
        }
    }
    /**
     * Get total amount paid
     */
    public function getTotalPaid()
    {
        $salesFlatInvoice = Mage::getSingleton('core/resource')->getTableName('sales_flat_invoice');
        $salesFlatInvoiceItem = Mage::getSingleton('core/resource')->getTableName('sales_flat_invoice_item');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write'); 
        $supplierId = Mage::getSingleton('admin/session')->getUser()->getUserId();
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        $select = "SELECT sum( `row_total_incl_tax` + shipping_rate) AS total_paid, sum(base_row_total_incl_tax+ base_shipping_rate-ifnull(base_discount_amount,0)) as base_total_paid FROM `".$salesFlatInvoiceItem."` WHERE `supplier_id` =".$supplierId." AND parent_id IN (SELECT entity_id FROM `".$salesFlatInvoice."` WHERE `order_id` = ".$orderId.")";
        $result = $write->fetchRow($select);
        if($result['total_paid']!= null) {
			$totalPaid['total_paid'] = $result['total_paid'];
			$totalPaid['base_total_paid'] = $result['base_total_paid'];
		}	
        else {
			$totalPaid['total_paid'] = 0;
			$totalPaid['base_total_paid'] = 0;
		}
		return $totalPaid;		
    }
    /**
     * Get total amount refunded
     */
    public function getTotalRefunded()
    {
        $salesFlatCreditmemo = Mage::getSingleton('core/resource')->getTableName('sales_flat_creditmemo');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write'); 
        $supplierId = Mage::getSingleton('admin/session')->getUser()->getUserId();
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        $select = "SELECT sum(`grand_total`) AS total_refunded, sum(`base_grand_total`) AS base_total_refunded FROM `".$salesFlatCreditmemo."` WHERE `supplier_id` =".$supplierId." AND `order_id` = ".$orderId;
        $result = $write->fetchRow($select);
        if($result['total_refunded']!= null) {
			$totalRefunded['total_refunded'] = $result['total_refunded'];
			$totalRefunded['base_total_refunded'] = $result['base_total_refunded'];
		}	
        else {
			$totalRefunded['total_refunded'] = 0;
			$totalRefunded['base_total_refunded'] = 0;
		}	
		return $totalRefunded;
    }
    /**
     * Get total shipping amount
     */
    public function getTotalShipping()
    {
        $salesFlatOrderItem = Mage::getSingleton('core/resource')->getTableName('sales_flat_order_item');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write'); 
        $supplierId = Mage::getSingleton('admin/session')->getUser()->getUserId();
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        $select = "SELECT sum(`or`.`shipping_rate`) as total_shipping, sum(`or`.`row_total_incl_tax` + `or`.`shipping_rate`) as order_total, sum(`or`.`base_shipping_rate`) as base_total_shipping, sum(`or`.`base_row_total_incl_tax` + `or`.`base_shipping_rate`) as base_order_total FROM `".$salesFlatOrderItem."` as `or`   WHERE (or.supplier_id= ".$supplierId." and `or`.`order_id` = ".$orderId ."  )";
        $result = $write->fetchRow($select);
        if(count($result)>0) {
            $total['total_shipping'] = $result['total_shipping'];
            $total['order_total'] = $result['order_total'];
			$total['base_total_shipping'] = $result['base_total_shipping'];
            $total['base_order_total'] = $result['base_order_total'];
        } else {
            $total['total_shipping'] = 0;
            $total['order_total'] = 0;
			$total['base_total_shipping'] = 0;
            $total['base_order_total'] = 0;
		}	
        return $total;
    }
    /**
     * Get Shipping amount refunded
     */
    public function getShippingRefunded()
    {
        $salesFlatCreditmemo = Mage::getSingleton('core/resource')->getTableName('sales_flat_creditmemo');
        $write = Mage::getSingleton('core/resource')->getConnection('core_write'); 
        $supplierId = Mage::getSingleton('admin/session')->getUser()->getUserId();
        $orderId = Mage::app()->getRequest()->getParam('order_id');
        $select = "SELECT sum(`ce`.`shipping_rate`) as shipping_refunded, sum(`ce`.`base_shipping_rate`) as base_shipping_refunded  FROM `".$salesFlatCreditmemo."` AS `ce` WHERE (ce.supplier_id= ".$supplierId." and `ce`.`order_id` = ".$orderId ."  )";
        $result = $write->fetchRow($select);
        if($result['shipping_refunded']!= null) {
            $shippingRefunded['shipping_refunded'] = $result['shipping_refunded'];
			$shippingRefunded['base_shipping_refunded'] = $result['base_shipping_refunded'];
		}  else {
			$shippingRefunded['shipping_refunded'] = 0;
		    $shippingRefunded['base_shipping_refunded'] =0;
		}
		return $shippingRefunded;		
    }
    /* 
     * Send Login Details email when suppliers activated first time
     */
    public function sendLoginDetails($firstName,$username,$email,$password, $emailTemplate)
    {
        /* Create an array of variables to assign to template */
        $emailTemplateVariables = array();    
        $emailTemplateVariables['rec_first_name'] = ucwords($firstName);
        $emailTemplateVariables['rec_user_name'] = $username;
        $emailTemplateVariables['rec_password'] = $password;
        $emailTemplateVariables['sender_name'] = Mage::getStoreConfig('trans_email/ident_general/name');           
        $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name'));
        $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
        $emailTemplate->setTemplateSubject('Activation');
        try {
            $emailTemplate->send($email,$firstName, $emailTemplateVariables);
        } catch (Mage_Core_Exception $e) {
            $this->_redirectError(Mage::getUrl('*/*/index', array('_secure' => true)));
        }
    }
    /* 
     * Send username in email to suppliers when admin change username
     */
    public function sendUsername($firstName,$userName,$email,$emailTemplate,$status)
    {
        /* Create an array of variables to assign to template */
        $emailTemplateVariables = array();    
        $emailTemplateVariables['rec_first_name'] = ucwords($firstName);
        $emailTemplateVariables['rec_user_name'] = $userName;
        $emailTemplateVariables['sender_name'] = Mage::getStoreConfig('trans_email/ident_general/name');             
        $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name'));
        $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
        $emailTemplate->setTemplateSubject($status);
        try {
            $emailTemplate->send($email,$firstName, $emailTemplateVariables);
        }
        catch (Mage_Core_Exception $e) {
        }
    }
}