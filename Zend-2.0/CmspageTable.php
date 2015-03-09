<?php
/**
 * CodeStd
 * Model to fetch data from the cms_pages table
 */
namespace Cmspage\Model;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\ResultSet\AbstractResultSet;
use Zend\Db\ResultSet\ResultSet;
use Login\Service\CommonFunctions;

class CmspageTable {
   protected $tableGateway;

   public function __construct(TableGateway $tableGateway) {
      $this->tableGateway = $tableGateway;
   }
   
   /**
   * fetching all data
   */
   public function fetchAll($optionArray=array()) {
      $adapter = $this->tableGateway->getAdapter();
      $sql     = new Sql($adapter);
      $select = $sql->select();
      $select->from($this->tableGateway->table);
      
      if(!empty($optionArray['fieldArray'])){
	 $select->columns($optionArray['fieldArray']);	
      }
      if(!empty($optionArray['sortByColumn']['sort_column']) && !empty($optionArray['sortByColumn']['sort_order'])){
	 $orderBy=$optionArray['sortByColumn']['sort_column'].' '.$optionArray['sortByColumn']['sort_order'];
	 $select->order($orderBy);	
      }
      else{
	 if(!empty($optionArray['default_sort_column']) && !empty($optionArray['default_sort_order'])){
	    $orderBy=$optionArray['default_sort_column'].' '.$optionArray['default_sort_order'];
	    $select->order($orderBy);	
	 }
      }
      if(!empty($optionArray['searchColumns']['searchKey']) && !empty($optionArray['searchColumns']['searchCol'])){
	 $searchKey="%".$optionArray['searchColumns']['searchKey']."%";
	 foreach($optionArray['searchColumns']['searchCol'] as $searchCol){
	    $search[] = "$searchCol LIKE '$searchKey'";
	 }
	 if(count($search)>0){
	    $whereStr = implode(' OR ',$search);
	 }
	 else{
	    $whereStr = '';
	 }
      }
      else{
	 $whereStr = '';
      }
      if ($whereStr != '') {
	 $select->where($whereStr);
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
   
   /**
   * getting cmspage on the basis of page id
   */
   public function getCmspage( $whereArray = array()) {
      $adapter = $this->tableGateway->getAdapter();
      $sql     = new Sql($adapter);
      $select  = $sql->select();
      $select->from($this->tableGateway->table)->where($whereArray);
      $statement = $sql->getSqlStringForSqlObject($select);
      $results   = $adapter->query($statement, $adapter::QUERY_MODE_EXECUTE);
      $results = $results->current();
      if ($results) {
	 if($results->count()!=0){
	    return $results;
	 }
	 else {
	    return false;
	 } 
      }
      else {
	 return false;
      }
   }

   /**
   * inserting data into database if column not exist,if exist then update
   */
   public function saveCmspage(Cmspage $cmspage) {
      $commonFnObj = new CommonFunctions();
      $pageId = $commonFnObj->getGUID();
      $data = array(
	       'ID' => $pageId,				
	       'CREATED_ID' => $cmspage->CREATED_ID,
	       'CREATED_DATE' => $cmspage->CREATED_DATE,					
	       'MODIFIED_ID' => $cmspage->MODIFIED_ID,
	       'MODIFIED_DATE' => $cmspage->MODIFIED_DATE,
	       'DELETED' => $cmspage->DELETED,
	       'TEXT' => $cmspage->TEXT,
	       'DESCRIPTION' => $cmspage->DESCRIPTION,
	       'DESCRIPTION_CODE' => $cmspage->DESCRIPTION_CODE,		
	       'USE_BY_CONTACTS' => $cmspage->USE_BY_CONTACTS,
	       'USE_BY_LAW' => $cmspage->USE_BY_LAW,
	       'USE_BY_CASES' => $cmspage->USE_BY_CASES
	    );	

      $id = ($cmspage->ID ? $cmspage->ID : 0);
      if ($id == 0) {
	 $this->tableGateway->insert($data);
	 return $pageId;
      }
      else {
	 $whereArray = array('ID'=>$id );
	 if ($this->getCmspage($whereArray)) {
	    $this->tableGateway->update($data, $whereArray);
	 } 
      }
   }
   
   /**
   * update the data
   */
   public function updateDetail($dataArray = array(), $whereArray = array()){
      $result = $this->tableGateway->update($dataArray,$whereArray);
      return $result;
   }

   /**
   * deleting cms page
   */
   public function deleteCmspage($whereArray = array()) {
      $this->tableGateway->delete($whereArray);
   }
   
}