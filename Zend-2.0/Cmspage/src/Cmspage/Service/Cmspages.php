<?php
/**
 * Module name: CMS page
 * File: Service class to define fields to be displayed on cms list page; each field has enable and disable feature defined in this class;
 * It is basically a class to get the columns with sort, search and visiblility feature on list page
**/

namespace Cmspage\Service;

class Cmspages{
    
    /**
     * Method to define required fields on the list page
    **/
    public function getFields(){
        $fieldsConfigArray = array(
			array(
			    'fieldName' => 'page_id',
			    'label' => '',
			    'visible' => 0,
			    'sorting' => 0,
			    'searching' => 0,
			),
			array(
			    'fieldName' => 'page_name',
			    'label' => 'Page Name',
			    'visible' => 1,
			    'sorting' => 1,
			    'searching' => 1,
			),
			array(
			    'fieldName' => 'last_modified',
			    'label' => 'Last Modified',
			    'visible' => 1,
			    'sorting' => 1,
			    'searching' => 0,
			),
			array(
			    'fieldName' => 'status',
			    'label' => 'Status',
			    'visible' => 1,
			    'sorting' => 0,
			    'searching' => 0,
			)
		);
		return $fieldsConfigArray;
    }
}