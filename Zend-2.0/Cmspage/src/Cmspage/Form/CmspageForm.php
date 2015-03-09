<?php
/**
* Module name: CMS page
* File: Cms page Form
*/

namespace Cmspage\Form;

// uses basic form defined in Application folder
use Application\Form\MyForm;

class CmspageForm extends MyForm {
    
    /**
     * Define the required form fields with their attributes
    **/
    public function __construct($name = null) {
        // define the name of form as cmspage
        parent::__construct('cmspage');
        
        //adding hidden element for page id; this field is useful when form is in edit mode
        $this->addHidden("page_id");
    
        $this->addTextBox("page_name", '', '', true, 'longname');
        
        $this->addTextArea('content', array('class'=>'tinyEditor'), '', true);
    
        $this->addRadioButton('status', '', array(
                                                'value_options' => array(
                                                    1 => 'Publish',
                                                    0 => 'Unpublish'
                                                )
                                            )
                            );
    
        $this->addTextArea('meta_keywords', '', '', false);
        
        $this->addTextArea('meta_description', '', '',false);
        
        $this->addTextBox("meta_title", '', '', false);
        
        $this->addTextBox("url_key", '', '', true,'longname');
        
        $this->addSubmit("submit", array('id'=>'submitbutton'));
    }
    
}