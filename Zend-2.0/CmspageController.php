<?php
/**
 * CodeStd
 * Cmspage Controller
 */
namespace Cmspage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Cmspage\Model\Cmspage;         
use Cmspage\Form\CmspageForm;
use Zend\Session\Container;
use Zend\Mvc\MvcEvent;
use Cmspage\Service\Cmspages;


class CmspageController extends AbstractActionController {
	protected $cmspageTable;
	protected $container;
	public function __construct(){
		$this->container = new Container('namespace');
	}

	/**
	* check if the admin is login otherwise redirect it to login page
	*/
	public function onDispatch(\Zend\Mvc\MvcEvent $e){
		if(!isset($this->container->admin_id)){
			return $this->redirect()->toRoute('login');
		}
		return parent::onDispatch($e);
	}

	/**
	* fetch data of cms page to show grid (with pagination)
	*/
	public function indexAction(){
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
		$fields = $cmsPagesClassObj->getFields();
		foreach($fields as $field){
			$optionArray['fieldArray'][]=$field['fieldName'];
			if($field['searching']==1){
				$optionArray['searchColumns']['searchCol'][]=$field['fieldName'];
			}
		}
		$optionArray['default_sort_column']='MODIFIED_ID';
		$optionArray['default_sort_order']='DESC';
		$paginator=$this->getCmspageTable()->fetchAll($optionArray);
		
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
	* add CMS page action 
	*/
	public function addAction(){
		$form = new CmspageForm();
		$request = $this->getRequest();
		if ($request->isPost()) {
			$cmspage = new Cmspage();
			$formData = $form->isMyFormDataValid($request);
			if($formData){
				/*validate code is unique */
				$isValid = $this->getCmspageTable()->getCmspage(array('DESCRIPTION_CODE'=> $formData['DESCRIPTION_CODE']));
				if(!$isValid){
					$date = date('Y-m-d H:i:s',time());
					$dataArray = array(					     
							"CREATED_DATE" => $date,
							"CREATED_ID" => $this->container->admin_id,
							"MODIFIED_DATE" => $date,
							"MODIFIED_ID" => $this->container->admin_id,
							"DELETED" => '0',
							"TEXT" => $formData['TEXT'],
							"DESCRIPTION" => $formData['DESCRIPTION'],
							"DESCRIPTION_CODE" => $formData['DESCRIPTION_CODE'],
							"USE_BY_CONTACTS" => '0',
							"USE_BY_LAW" => '0',
							"USE_BY_CASES" => '0',
						);
					$cmspage->exchangeArray($dataArray);
					$this->getCmspageTable()->saveCmspage($cmspage);
					// Redirect to list of cmspages
					$this->container->message="CMS page has been added successfully";
					return $this->redirect()->toRoute('cmspage');
				}
				else {
					return array('form' => $form, 'invalidCode'=> 1);
				}
			}
			else {
				return array('form' => $form);
			}
		}
		return array('form' => $form);
	}

	/**
	* edit CMS page action 
	*/
	public function editAction(){
		$id = $this->params()->fromRoute('id', 0);
		if (!$id) {
			return $this->redirect()->toRoute('cmspage', array(
				'action' => 'add'
			));
		}
		try {
		    $cmspage = $this->getCmspageTable()->getCmspage(array('ID'=>$id));
		}
		catch (\Exception $ex) {
			return $this->redirect()->toRoute('cmspage', array(
				'action' => 'index'
			));
		}		
		$form  = new CmspageForm();
		$form->bind($cmspage);
		$original = $cmspage->DESCRIPTION_CODE;
		$request = $this->getRequest();
		if ($request->isPost()) {
			$cmsData = $form->isMyFormDataValid($request);
			if($cmsData){
				/*validate code is unique */
				if($cmsData->DESCRIPTION_CODE != $original) {    
				     $isValid = $this->getCmspageTable()->getCmspage(array('DESCRIPTION_CODE'=> $cmsData->DESCRIPTION_CODE));
				     if($isValid){
					return array(
						'id' => $id,
						'form' => $form,
						'cmsPageData' => $cmspage,
						'originalcode' => $original,
						'invalidCode' =>1,
				        );
				     }
			        }
			        $date = date('Y-m-d',time());
				$dataArray = array(				     
					"MODIFIED_DATE"	=> $date,
					"MODIFIED_ID" => $this->container->admin_id,
					"TEXT" => $cmsData->TEXT,
					"DESCRIPTION" => $cmsData->DESCRIPTION,
					"DESCRIPTION_CODE" => $cmsData->DESCRIPTION_CODE,
					"DELETED" => '0',
					"USE_BY_CONTACTS" => '0',
					"USE_BY_LAW" => '0',
					"USE_BY_CASES" => '0',
				);
				$whereArray = array('ID' =>$cmsData->ID);
				$this->getCmspageTable()->updateDetail($dataArray, $whereArray);
				// Redirect to list of cmspages
				$this->container->message="CMS page has been updated successfully";
				return $this->redirect()->toRoute('cmspage');
			}
			else {
				return array(
					'id' => $id,
					'form' => $form,
					'cmsPageData'=>$cmspage,
					'originalcode' => $original,
				);
			}
		}
		return array(
			'id' => $id,
			'form' => $form,
			'cmsPageData'=>$cmspage,
			'originalcode' => $original,
		);
	}

	/**
	* delete CMS page action 
	*/
	public function deleteAction(){
		$id = $this->params()->fromRoute('id', 0);
		//disabling layout
		$view = new ViewModel(array('id' => $id));
		$view->setTerminal(true);
		if($id == 'pid') {
			$id = '';
			$id = $this->params()->fromRoute('set-status');
			if($id != '') {
				$whereArray = array('ID'=> $id);
				$this->getCmspageTable()->deleteCmspage($whereArray);
				// Redirect to list of cmspages
				$this->container->message="CMS page has been deleted successfully";
				return $this->redirect()->toRoute('cmspage');
			}
		}
		return $view;
	}

	/**
	* getting cms_page table obj
	*/
	public function getCmspageTable(){
		if (!$this->cmspageTable) {
			$sm = $this->getServiceLocator();
			$this->cmspageTable = $sm->get('Cmspage\Model\CmspageTable');
		}
		return $this->cmspageTable;
	}
	
	/**
	* view action of admin
	*/
	public function viewAction(){
		$id = $this->params()->fromRoute('id', 0);
		try {
			if(!empty($id)){
				$whereArray = array('id'=> $id);
				$cmspage = $this->getCmspageTable()->getCmspage($whereArray);
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
	* checking uniqueness of code on ajax call
	*/
	public function checkcodeAction(){
		$request = $this->getRequest();
		$results = $request->getPost();		
		$view = new ViewModel();
		$view->setTerminal(true);
		if(!empty($results['code'])){			
			$whereArray=array('description_code'=>$results['code']);
			$cmspage = $this->getCmspageTable()->getCmspage($whereArray);
			if($cmspage){
				echo '1';
			}
			else{
				echo '2';
			}
		}
		die;		
	}
 }