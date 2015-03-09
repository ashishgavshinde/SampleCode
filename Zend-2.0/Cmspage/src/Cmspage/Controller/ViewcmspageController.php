<?php
/**
 * Module name: CMS page
 * File: ViewcmspageController is develoed to show CMS page to frontend user
**/

namespace Cmspage\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Mvc\MvcEvent;
use Cmspage\Model\Cmspage;
use Cmspage\Service\Cmspages;

class ViewcmspageController extends AbstractActionController {
	
	/**
	 * protected member variables
	**/
	protected $cmspageTable;
	protected $container;
	
	/**
	 * Action to display cmspage to the frontend user
	**/
	public function indexAction() {
		$params = $this->params()->fromRoute();
		try {
			if(!empty($params['page_name'])) {
				$whereArray=array('url_key'=>$params['page_name'],'status'=>1);
				$cmspage = $this->getCmspageTable()->getCmspageByPagename($whereArray);
				return array(
					'cmsPageData'=>$cmspage,
				);	
			}
		}
		catch (\Exception $ex) {
			return $this->redirect()->toRoute('home');
		}
	}
	
	/**
	 * Action to get cms_page table object
	**/
	public function getCmspageTable() {
		if (!$this->cmspageTable) {
			$sm = $this->getServiceLocator();
			$this->cmspageTable = $sm->get('Cmspage\Model\CmspageTable');
		}
		return $this->cmspageTable;
	}
	
}