<?php
/**
 * @category    Alw
 * @package     Alw_Suppliers
 * Supplier controller
 */
class Alw_Suppliers_IndexController extends Mage_Core_Controller_Front_Action
{
    /* 
	 * Action for the supplier registration page
	 */
    public function indexAction()
    {
        $this->loadLayout();   
        $this->getLayout()->getBlock('head')->setTitle($this->__('Supplier Registration'));        
        $this->renderLayout();
    }
    /* 
	 * Prepare session to add message in session
	 */
    protected function _getSession()
    {
        return Mage::getSingleton('core/session');
    }
    /* 
	 * Save the supplier registration records in database and send notifications to admin and registered user
	 */
    public function registerpostAction() 
    {
        if ($this->getRequest()->isPost()) {
            $helper = Mage::helper('adminhtml');
            $formData = $this->getRequest()->getPost();
            $session = $this->_getSession();
            try {
                $user = Mage::getModel('admin/user');
                $formData['is_active'] = 2;
				$formData['supplier_createdat'] = now();
				$formData['commission'] = Mage::getStoreConfig('suppliers/suppliers/commissions');
                $user->setData($formData);
                $iscompanyExists = Mage::getModel('suppliers/suppliers')->companyExists(($this->getRequest()->getParam('company', false)),''); 
                if ($iscompanyExists == 1) {
                    $session->addError('Company Name already exists');
                    Mage::getSingleton('core/session')->setPostValue($this->getRequest()->getPost());
                    $this->_redirectError(Mage::getUrl('*/*/index', array('_secure' => true)));
                    return;
                }
                $user->save();
                Mage::getSingleton('core/session')->setPostValue('');
                /*
                * Loads the html file named 'agentregistrationemail.html' from
                * app/locale/en_US/template/email/suppliers/supplierregistration.html for supplier notification
                */
                $emailTemplate  = Mage::getModel('core/email_template')->loadDefault('supplier_registration_email');
                /* Create an array of variables to assign to template */
                $emailTemplateVariables = array();
                $emailTemplateVariables['rec_first_name'] = ucwords($formData['firstname']);
                $emailTemplateVariables['rec_last_name'] = ucwords($formData['lastname']);
                $emailTemplateVariables['sender_name'] = Mage::getStoreConfig('trans_email/ident_general/name');
                $emailTemplate->setSenderName(Mage::getStoreConfig('trans_email/ident_general/name'));
                $emailTemplate->setSenderEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
                $emailTemplate->setTemplateSubject('Confirmation');
                try {
                    $emailTemplate->send($email,$formData['first_name'], $emailTemplateVariables);
                } catch (Mage_Core_Exception $e) {
                    $this->_redirectError(Mage::getUrl('*/*/index', array('_secure' => true)));
                } catch (Exception $e) {
                    $session->addError($e->getMessage());
                    $this->_redirectError(Mage::getUrl('*/*/index', array('_secure' => true)));    
               } 
                /*
                * Loads the html file named 'adminemail.html' from
                * app/locale/en_US/template/email/suppliers/adminemail.html for admin notification
                */
                $emailAdminTemplate = Mage::getModel('core/email_template')->loadDefault('admin_supplier_registration');
                /*Create an array of variables to assign to template*/
                $emailAdminTemplateVariables = array();
                $emailAdminTemplateVariables['user_first_name'] = ucwords($formData['firstname']);
                $emailAdminTemplateVariables['user_last_name'] = ucwords($formData['lastname']);
                $emailAdminTemplate->setSenderName($fullName);
                $emailAdminTemplate->setSenderEmail($email);
                $emailAdminTemplate->setTemplateSubject('Notification');
                $emailAdminTemplate->send(Mage::getStoreConfig('trans_email/ident_general/email'), Mage::getStoreConfig('trans_email/ident_general/name'), $emailAdminTemplateVariables); 
                $this->_redirectSuccess(Mage::getUrl('*/*/success', array('_secure'=>true)));    
                try {
                $roleId = Mage::getModel('suppliers/suppliers')->getSuppliers();
                    /*assign user to role*/
                    $user->setRoleIds(array($roleId))
                        ->setRoleUserId($user->getUserId())
                        ->saveRelations();
                } catch (Exception $e) {
                    Mage::getSingleton('core/session')->setPostValue($this->getRequest()->getPost());
                    $session->addError($e->getMessage());
                    $this->_redirectError(Mage::getUrl('*/*/index', array('_secure' => true)));
                } 
            } catch (Exception $e) {
                Mage::getSingleton('core/session')->setPostValue($this->getRequest()->getPost());
                $session->addError($e->getMessage());
                $this->_redirectError(Mage::getUrl('*/*/index', array('_secure' => true)));
            }
        }
    }
    /* 
     * Success Action
	 */
    public function successAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Success'));
        $this->renderLayout();
    }
    /* 
	 * Get State on change drop down
	 */
    public function stateOnChangeAction() 
    {
	    /* Get country code from parameter */
        $countrycode = $this->getRequest()->getParam('country');
        $stateCode = $this->getRequest()->getParam('state');
        /* Get all state array according to the country code */
        $statearray =Mage::getResourceModel('directory/region_collection') ->addCountryFilter($countrycode)->load();
        if ($countrycode) {
            if (count($statearray)>1) {
                /* 
				 * If state array has multiple option show select box else input box
				 */
                $state = "
                <select id='state' name='state' class='required-entry validate-state'>
                    <option value=''>Please Select</option>";
                if (!empty($arrRegions)) {
                    foreach ($arrRegions as $region) {
                        $arrRes[] = $region;
                    }
                }
               foreach ($statearray as $_state) {
                    if ($_state->getId()==$stateCode||$_state->getCode() == $stateCode) {
                        $state .= "<option value='" . $_state->getCode() . "' selected='selected'>" . $_state->getDefaultName() . "</option>";
                    } else {
                        $state .= "<option value='" . $_state->getCode() . "'>" . $_state->getDefaultName() . "</option>";
                    }
                }
                $state .="</select>";
            } else {
                $state ='<input id="state" name="state" value="'.$stateCode.'" title="state" type="text" class=" input-text required-entry">';
            }
        } else {
            $state ='<input id="state" name="state" value="'.$stateCode.'" title="state" type="text" class=" input-text required-entry">';
        }
        $result['state'] = $state;
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
    
    /* 
	 * Action to show supplier product list
	 */
    public function productListAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Supplier List'));
        $this->renderLayout();
    }
    /* 
	 * Action to show supplier detail page
	 */
    public function supplierDetailAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
	/* 
	 * Action to show supplier detail page
	 */   
	public function termconditionAction()    
	{
		echo Mage::getStoreConfig('suppliers/suppliers/termsandcondition');
	}
}