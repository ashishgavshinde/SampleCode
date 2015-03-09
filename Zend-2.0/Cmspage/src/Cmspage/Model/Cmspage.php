<?php
/**
 * Module name: CMS page
 * Files: Model class to define the attributes of table in the class and form validations on each (validations depend upon the requirement)
**/

namespace Cmspage\Model;

// use pre-defined Zend input filter classes
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Cmspage {
   
   /**
    * public and protected variables
   **/
   public $page_id;
   public $page_name;
   public $content;
   public $status;
   public $publish_status;
   public $is_deletable;
   public $is_dynamic;
   public $meta_keywords;
   public $meta_description;
   public $meta_title;
   public $url_key;
   public $last_updated_by;
   public $last_modified;
   protected $inputFilter;

   /**
    * set the public variables
   **/
   public function exchangeArray($data) {
      $this->page_id     = (!empty($data['page_id'])) ? $data['page_id'] : null;
      $this->page_name = (!empty($data['page_name'])) ? $data['page_name'] : null;
      $this->content  = (!empty($data['content'])) ? $data['content'] : null;
      $this->status  = (!empty($data['status'])) ? $data['status'] : null;
      $this->publish_status  = (!empty($data['publish_status'])) ? $data['publish_status'] : null;
      $this->is_deletable  = (!empty($data['is_deletable'])) ? $data['is_deletable'] : null;
      $this->is_dynamic  = (!empty($data['is_dynamic'])) ? $data['is_dynamic'] : null;
      $this->meta_keywords  = (!empty($data['meta_keywords'])) ? $data['meta_keywords'] : null;
      $this->meta_title  = (!empty($data['meta_title'])) ? $data['meta_title'] : null;
      $this->meta_description  = (!empty($data['meta_description'])) ? $data['meta_description'] : null;
      $this->url_key  = (!empty($data['url_key'])) ? $data['url_key'] : null;
      $this->last_updated_by  = (!empty($data['last_updated_by'])) ? $data['last_updated_by'] : null;
      $this->last_modified  = (!empty($data['last_modified'])) ? $data['last_modified'] :time();
   }
   
   /**
    * Add content to these methods
   **/
   public function setInputFilter(InputFilterInterface $inputFilter) {
      throw new \Exception("Not used");
   }
   /**
   * Input form validations for add cms page
   */
   public function getInputFilter() {
      if (!$this->inputFilter) {
         $inputFilter = new InputFilter();
         $inputFilter->add(
            array(
               'name'     => 'page_name',
               'required' => true,
               'filters'  => array(
                     array('name' => 'StripTags'),
                     array('name' => 'StringTrim'),
                  ),
               'validators' => array(
                  array(
                     'name'    => 'StringLength',
                     'options' => array(
                        'encoding' => 'UTF-8',
                        'min'      => 1,
                        'max'      => 100,
                     ),
                  ),
               ),
            )
         );
         
         $inputFilter->add(
            array(
               'name'     => 'url_key',
               'required' => true,
               'filters'  => array(
                  array('name' => 'StripTags'),
                  array('name' => 'StringTrim'),
               ),
               'validators' => array(
                  array(
                     'name'    => 'StringLength',
                     'options' => array(
                        'encoding' => 'UTF-8',
                        'min'      => 1,
                        'max'      => 100,
                     ),
                  ),
               ),
            )
         );
   
         $inputFilter->add(
            array(
               'name'     => 'status',
               'required' => true
            )
         );
   
         $this->inputFilter = $inputFilter;
      }
      return $this->inputFilter;
   }
   
   /**
    * Input form validations for edit cms page
   **/
   public function getInputEditFilter() {
      if (!$this->inputFilter) {
         $inputFilter = new InputFilter();
         $inputFilter->add(
            array(
               'name'     => 'page_name',
               'required' => true,
               'filters'  => array(
                  array('name' => 'StripTags'),
                  array('name' => 'StringTrim'),
               ),
               'validators' => array(
                  array(
                     'name'    => 'StringLength',
                     'options' => array(
                        'encoding' => 'UTF-8',
                        'min'      => 1,
                        'max'      => 100,
                     ),
                  ),
               ),
            )
         );
         
         $inputFilter->add(
            array(
               'name'     => 'status',
               'required' => true
            )
         );
      
         $this->inputFilter = $inputFilter;
      }      
      return $this->inputFilter;
   }
   
   public function getArrayCopy() {
      return get_object_vars($this);
   }
   
}