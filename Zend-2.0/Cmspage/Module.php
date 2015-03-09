<?php
/**
 * Module name: CMS page
 * File: Module file
**/

// specify the namespace of module and use it across the site with this name
namespace Cmspage;

use Cmspage\Model\Cmspage;
use Cmspage\Model\CmspageTable;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\Event;
use Zend\Mvc\ModuleRouteListener;
use Zend\ModuleManager\ModuleManager;

class Module {

   /**
    * Intialize module
   **/
   public function onBootstrap(MvcEvent $e) {
      $eventManager = $e->getApplication()->getEventManager();
      $sm = $e->getApplication()->getServiceManager();
      $sm->get('BaconAssetLoader.AssetManager')->getEventManager()->attach(
	 'collectAssetInformation',
	 function(Event $event) {
	    $event->getTarget()->addAssets(new AssetCollection(__DIR__ . '/public'));
	 }
      );
      $renderer = $sm->get('viewhelpermanager')->getRenderer();
      
      $eventManager->getSharedManager()->attach(
	 'Zend\Mvc\Controller\AbstractActionController',
	 'dispatch',
	 function($e) use ($renderer){
	    $match = $e->getRouteMatch();
	    $configValues = include __DIR__ . '/config/module.config.php';
	    
	    //setting layout on the basis of route matched
	    if($match->getMatchedRouteName() == 'cmspage') {
	       $controller = $e->getTarget();
	       $route = $controller->getEvent()->getRouteMatch();
	       $actionName=$route->getParam('action');
	       
	       if($actionName!='view') {
		  $controller->layout('adminlogin/adminlayout');
	       }
	       
	       // include javasripts
	       if(isset($configValues['jsincludes'])) {
		  foreach($configValues['jsincludes']['cmspage'] as $route => $jsName) {
		     if(in_array($jsName,array('tiny_mce.js','tiny_mce_src.js'))) {
			$renderer->headScript()->appendFile($renderer->basePath().'/js/admin/tiny_mce/'.$jsName);
		     }
		     else {
			$renderer->headScript()->appendFile($renderer->basePath().'/js/admin/'.$jsName);
		     }	 
		  }       
	       }
	       
	       // include css
	       if(isset($configValues['cssincludes'])) {
		  foreach($configValues['cssincludes']['cmspage'] as $route => $cssName){
		     $renderer->headLink()->appendStylesheet($renderer->basePath().'/css/admin/'.$cssName);
		  }
	       }
	    }
	 },
	 100
      );
      
      // Initialize route listner and attach event manager to it
      $moduleRouteListener = new ModuleRouteListener();
      $moduleRouteListener->attach($eventManager);
      
      // Create required table in database
      $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
      $configCreate="CREATE TABLE IF NOT EXISTS `cms_pages` (
			`page_id` smallint(6) NOT NULL AUTO_INCREMENT,
			`page_name` varchar(50) DEFAULT NULL,
			`content` text,
			`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=inactive, 1=Active',
			`publish_status` tinyint(1) DEFAULT NULL,
			`is_deletable` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0=not deletable, 1=deletable',
			`is_dynamic` tinyint(1) NOT NULL DEFAULT '0',
			`meta_keywords` text,
			`meta_description` text,
			`meta_title` varchar(255) DEFAULT NULL,
			`url_key` varchar(255) DEFAULT NULL COMMENT 'seo friendly url key',
			`last_updated_by` smallint(6) DEFAULT NULL COMMENT 'The admin user that made the lastet change',
			`last_modified` int(11) NOT NULL,
			`microsite_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Id of the micro site associated with the page, 0 if page is associated with main site',
			PRIMARY KEY (`page_id`,`microsite_id`),
			UNIQUE KEY `page_name` (`page_name`,`microsite_id`)
		     )
		     ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";
      $configResult = $dbAdapter->query($configCreate);
      
      // If query is formulated properly then execute..
      if($configResult){
	 $configResult->execute();
      }
   }
   
   /**
    * Load the module
   **/
   public function getAutoloaderConfig() {
      return array(
	       'Zend\Loader\StandardAutoloader' => array(
		     'namespaces' => array(
			   __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
			),
		     ),
	    );
   }

   /**
    * Include the configuration file of module
   **/
   public function getConfig() {
      return include __DIR__ . '/config/module.config.php';
   }
   
   /**
    * Initialize the services which module will use like models, helpers etc
   **/
   public function getServiceConfig() {
      return array(
	 'factories' => array(
	    'Cmspage\Model\CmspageTable' =>  function($sm) {
	       $tableGateway = $sm->get('CmspageTableGateway');
	       $table = new CmspageTable($tableGateway);
	       return $table;
	    },
	    'CmspageTableGateway' => function ($sm) {
	       $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
	       $resultSetPrototype = new ResultSet();
	       $resultSetPrototype->setArrayObjectPrototype(new Cmspage());
	       return new TableGateway('cms_pages', $dbAdapter, null, $resultSetPrototype);
	    },
	 ),
      );
   }
   
}
