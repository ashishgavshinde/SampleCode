<?php
/**
 * Module name: CMS page
 * File: CMSPageController contains add, edit, view and delete actions
**/

namespace Cmspage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Zend\Mvc\MvcEvent;
use Cmspage\Model\Cmspage;         
use Cmspage\Form\CmspageForm;
use Cmspage\Service\Cmspages;

class CmspageController extends AbstractActionController{

	/**
	 * protected member variables
	**/
	protected $cmspageTable;
	protected $container;
	
	/**
	 * define session variable for this class
	**/
	public function __construct() {
		$this->container = new Container('namespace');
	}

	/**
	 * check if the admin is login otherwise redirect it to login page
	**/
	public function onDispatch(\Zend\Mvc\MvcEvent $e){
		if(!isset($this->container->admin_id)){
			return $this->redirect()->toRoute('adminlogin');
		}
		return parent::onDispatch($e);
	}

	/**
	 * fetch all the data from data with pagination object
	**/
	public function indexAction() {
		$message='';
		if(isset($this->container->message)){
			$message=$this->container->message;
			unset($this->container->message);
		}
		$request = $this->getRequest();
		$params = $request->getQuery();
		$optionArray=array();
		if(!empty($params['sort']) && !empty($params['order'])){
			$optionArray['sortByColumn']['sort_column']=$params['sort'];
			$optionArray['sortByColumn']['sort_order']=$params['order'];
		}
		if(!empty($params['search'])){
			$optionArray['searchColumns']['searchKey']=$params['search'];
		}
		$cmsPagesClassObj=new Cmspages();
		$fields=$cmsPagesClassObj->getFields();
		foreach($fields as $field){
			$optionArray['fieldArray'][]=$field['fieldName'];
			if($field['searching']==1){
				$optionArray['searchColumns']['searchCol']=$field['fieldName'];
			}
		}
		$optionArray['default_sort_column']='page_name';
		$optionArray['default_sort_order']='ASC';
		$paginator=$this->getCmspageTable()->fetchAll($optionArray,true);
		
		// set the current page to what has been passed in query string, or to 1 if none set
		$page=(int)$this->params()->fromQuery('page', 1);
		$paginator->setCurrentPageNumber($page);
		
		// set the number of items per page to 10
		$serialNumber=($page-1)*10+1;
		$paginator->setItemCountPerPage(10);
		
		return new ViewModel(array(
			'cmspages' => $paginator,
			'message'=>$message,
			'fields'=>$fields,
			'showSearch'=>1,
			'defaultSortOrder'=>(($params['order']=='ASC' || empty($params['order']))?'DESC':'ASC'),
			'serialNumber'=>$serialNumber
		));
	}

	/**
	 * Action to add CMS page
	**/
	public function addAction(){
		$form = new CmspageForm();
		$request = $this->getRequest();
		
		// check if form is posted
		if ($request->isPost()) {
			$cmspage = new Cmspage();
			$formData = $form->isMyFormDataValid($request);
			if($formData){				
				$cmspage->exchangeArray($formData);
				$this->getCmspageTable()->saveCmspage($cmspage);
				
				// Redirect to list of cmspages
				$this->container->message="CMS page has been added successfully.";
				return $this->redirect()->toRoute('cmspage');
			}
		}
		return array('form' => $form);
	}

	/**
	 * Action to edit CMS page
	**/
	public function editAction(){
		$id = (int) $this->params()->fromRoute('id', 0);
		if (!$id) {
			return $this->redirect()->toRoute('cmspage', array('action' => 'add'));
		}
		try {
			$cmspage = $this->getCmspageTable()->getCmspage($id);
		}
		catch (\Exception $ex) {
			return $this->redirect()->toRoute('cmspage', array('action' => 'index'));
		}

		$form  = new CmspageForm();
		$cmspage->status=($cmspage->status)? $cmspage->status : 0;
		$form->bind($cmspage);
		$request = $this->getRequest();
		
		// check if form is posted
		if ($request->isPost()) {
			$formData = $form->isMyFormDataValid($request);
			if($formData){
				$this->getCmspageTable()->saveCmspage($cmspage);
				
				// Redirect to list of cmspages
				$this->container->message="CMS page has been updated successfully.";
				return $this->redirect()->toRoute('cmspage');
			}
		}
		
		return array(
			'id' => $id,
			'form' => $form,
			'cmsPageData'=>$cmspage,
		);
	}

	/**
	 * Action to delete CMS page
	**/
	public function deleteAction(){
		$id = $this->params()->fromRoute('id', 0);
		//disabling layout
		$view = new ViewModel(
				array(
					'id' => $id,
				)
		);

		$view->setTerminal(true);
		if($id='pid') {
			$id=(int) $this->params()->fromRoute('set-status', 0);
			if($id!=0) {
				$this->getCmspageTable()->deleteCmspage($id);
				
				// Redirect to list of cmspages
				$this->container->message="CMS page has been deleted successfully.";
				return $this->redirect()->toRoute('cmspage');
			}
		}
		return $view;
	}

	/**
	 * Action to change status of page; This action is called by ajax
	**/
	public function changestatusAction(){
		$params=$this->params()->fromRoute();
		if(!empty($params['id'])){
			$this->getCmspageTable()->changestatus($params['id'],$params['set-status']);
			$this->container->message="Status has been updated successfully.";
			return $this->redirect()->toRoute('cmspage');
		}
		die;
	}

	/**
	 * Action to view the CMS page
	**/
	public function viewAction(){
		$id = $this->params()->fromRoute('id', 0);
		try {
			if(!empty($id)){
				$cmspage = $this->getCmspageTable()->getCmspage($id);
				return array(
					'cmsPageData'=>$cmspage,
				);
			}
		}
		catch (\Exception $ex) {
			return $this->redirect()->toRoute('cmspage', array(
				'action' => 'index'
			));
		}
	}

	/**
	 * Action to check uniqueness of page name; called by ajax request
	**/
	public function checkuniqueAction(){
		$request = $this->getRequest();
		$results = $request->getQuery();
		if(!empty($results->pageTitle)){
			$whereArray=array('page_name'=>$results->pageTitle);
			$pageId=$results->pId;
			$cmspage = $this->getCmspageTable()->checkUniqueness($whereArray,$pageId);
			if($cmspage){
				echo '1';die;
			}else{
				echo '2';die;
			}
		}
	}

	/**
	 * Action to check uniqueness of url key; called by ajax request
	**/
	public function checkurlkeyAction(){
		$request = $this->getRequest();
		$results = $request->getQuery();
		if(!empty($results->urlKey)){
			$whereArray=array('url_key'=>$results->urlKey);
			$pageId=$results->pId;
			$cmspage = $this->getCmspageTable()->checkUniqueness($whereArray,$pageId);
			if($cmspage){
				echo '1';die;
			}else{
				echo '2';die;
			}
		}
	}
	
	/**
	 * Action to get cms_page table object
	**/
	public function getCmspageTable(){
		if (!$this->cmspageTable) {
			$sm = $this->getServiceLocator();
			$this->cmspageTable = $sm->get('Cmspage\Model\CmspageTable');
		}
		return $this->cmspageTable;
	}
	
 }