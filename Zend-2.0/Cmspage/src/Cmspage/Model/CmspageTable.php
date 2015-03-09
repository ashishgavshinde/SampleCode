<?php
/**
 * Module name: CMS page
 * File: Model class to fetch data from the cms_pages table
**/

namespace Cmspage\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\ResultSet\ResultSet;

class CmspageTable {
   
   /**
    * protected member variable
   **/
   protected $tableGateway;

   /**
    * intialize the table gateway variable for this class
   **/
   public function __construct(TableGateway $tableGateway) {
      $this->tableGateway = $tableGateway;
   }

   /**
    * Method to fetch all data in pagination object
   **/
   public function fetchAll($optionArray=array(),$paginated=false) {
      if($paginated) {
	 // create a new Select object for the table cmspage
	 $select = new Select('cms_pages');
	 if(!empty($optionArray['fieldArray'])) {
	    $select->columns($optionArray['fieldArray']);	
	 }
	 if(!empty($optionArray['sortByColumn']['sort_column']) && !empty($optionArray['sortByColumn']['sort_order'])) {
	    $orderBy=$optionArray['sortByColumn']['sort_column'].' '.$optionArray['sortByColumn']['sort_order'];
	    $select->order($orderBy);	
	 }
	 else {
	    if(!empty($optionArray['default_sort_column']) && !empty($optionArray['default_sort_order'])) {
	       $orderBy=$optionArray['default_sort_column'].' '.$optionArray['default_sort_order'];
	       $select->order($orderBy);	
	    }
	 }
	 
	 if(!empty($optionArray['searchColumns']['searchKey']) && !empty($optionArray['searchColumns']['searchCol'])) {
	    $searchKey="%".$optionArray['searchColumns']['searchKey']."%";
	    $searchCol=($optionArray['searchColumns']['searchCol']?$optionArray['searchColumns']['searchCol']:$optionArray['fieldArray'][1]);
	    $select->where->like($searchCol,$searchKey);
	 }
	 
	 // create a new result set based on the cmspage entity
	 $resultSetPrototype = new ResultSet();
	 $resultSetPrototype->setArrayObjectPrototype(new Cmspage());
	 
	 // create a new pagination adapter object
	 $paginatorAdapter = new DbSelect(	 
	    // our configured select object
	    $select,
	    // the adapter to run it against
	    $this->tableGateway->getAdapter(),
	    // the result set to hydrate
	    $resultSetPrototype
	 );
	 $paginator = new Paginator($paginatorAdapter);
	 return $paginator;
      }
      
      $resultSet = $this->tableGateway->select(
			function(Select $select) use ($optionArray) {
			   if(!empty($optionArray['fieldArray'])){
			      $select->columns($optionArray['fieldArray']);	
			   }
			   if(!empty($optionArray['sortByColumn']['sort_column']) && !empty($optionArray['sortByColumn']['sort_order'])) {
			      $orderBy=$optionArray['sortByColumn']['sort_column'].' '.$optionArray['sortByColumn']['sort_order'];
			      $select->order($orderBy);	
			   }
			   else{
			      if(!empty($optionArray['default_sort_column']) && !empty($optionArray['default_sort_order'])) {
				 $orderBy=$optionArray['default_sort_column'].' '.$optionArray['default_sort_order'];
				 $select->order($orderBy);	
			      }
			   }
			   if(!empty($optionArray['searchColumns']['searchKey']) && !empty($optionArray['searchColumns']['searchCol'])) {
			      $searchKey="%".$optionArray['searchColumns']['searchKey']."%";
			      $searchCol=($optionArray['searchColumns']['searchCol']?$optionArray['searchColumns']['searchCol']:$optionArray['fieldArray'][1]);
			      $select->where->like($searchCol,$searchKey);
			   }
			}
		     );
      return $resultSet;
   }
   
   /**
    * Method to get cmspage on the basis of page id
   **/
   public function getCmspage($id) {
      $id  = (int) $id;
      $rowset = $this->tableGateway->select(array('page_id' => $id));
      $row = $rowset->current();
      if (!$row) {
	 throw new \Exception("Could not find row $id");
      }
      return $row;
   }
   
   /**
    * Method to get cmspage on the basis of page name
   **/
   public function getCmspageByPagename($whereArray=array()) {
      $rowset = $this->tableGateway->select($whereArray);
      $row = $rowset->current();
      return $row;
   }
   
   /**
    * Method to check uniqueness of cms page name
   **/
   public function checkUniqueness($whereArray='',$pageId='') {
      $rowset = $this->tableGateway->select($whereArray);
      $row = $rowset->current();
      if($pageId != $row->page_id){
	 return $row;
      }
   }
   
   /**
    * Method to insert data into table if row does not exist, if exists then update
   **/
   public function saveCmspage(Cmspage $cmspage) {
   
      $update_date = time();      
      $data = array(
	       'page_name' => $cmspage->page_name,
	       'content'  => $cmspage->content,
	       'status'  => ($cmspage->status?$cmspage->status:0),
	       'publish_status'  => 0,
	       'meta_keywords'  => $cmspage->meta_keywords,
	       'meta_title'  => $cmspage->meta_title,
	       'meta_description'  => $cmspage->meta_description,
	       'url_key'  => $cmspage->url_key,
	       'last_updated_by'  => 0,
	       'last_modified' => $update_date      
	    );
      $id = (int) ($cmspage->page_id) ? $cmspage->page_id : 0;
      if($id == 0) {
	 $this->tableGateway->insert($data);
      }
      else {
	 if ($this->getCmspage($id)) {
	    $this->tableGateway->update($data, array('page_id' => $id));
	 }
	 else {
	    throw new \Exception('Cmspage page_id does not exist');
	 }
      }
   }
   
   /**
    * Method to change status of the cms page
   **/
   public function changestatus($pageId='',$status=0) {
      if(!empty($pageId)){
	 $status=($status) ? $status : 0;
	 $data=array('status'=>$status);
	 $this->tableGateway->update($data, array('page_id' => $pageId));
      }
   }
   
   /**
   * Method to delete cms page
   */
   public function deleteCmspage($id) {
      $this->tableGateway->delete(array('page_id' => (int) $id));
   }
   
}