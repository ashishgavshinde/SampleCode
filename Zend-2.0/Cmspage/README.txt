====================================================================================
====================================================================================
================= Read below to get better understanding of module =================
====================================================================================
====================================================================================

MODULE NAME : CMS page management in Admin section


PURPOSE : The main purpose of this module is to manage the CMS or static pages on the site in Admin section. Admin can add, edit or delete the created pages and can modify the status (enable or disable) of pages. Please include link in your admin layout file before running this module. Below is the sample code:

<a href="<?php echo $this->url('cmspage', array('action' => 'index')); ?>" class="list-group-item <?php if($controllerName=='Cmspage\Controller\Cmspage'){ echo 'active'; }?>"><?php echo $this->translate('Manage CMS Page'); ?></a>


TECHNOLOGY AND FRAMEWORK : PHP, Zend 2.0 Framework


FUNCTIONAL DESCRIPTION OF MODULE: This package is designed by considering the basic structure of Zend 2.0. It includes all required and necessary scripts to achieve the goal. Following is the brief description of files used in this module:

    1: /Module.php: Initializes the module. It includes the initialization of required services for the proper flow of data like Javascript, CSS, Models, Helpers, Table creation etc.
    
    2: /config/module.config.php: It configures the module in the application. Configuration regarding path of view files, invokable controllers, Javascript and CSS file which are used in the module. Routing algorithms can also be written here.
    
    3: /public : This folder contains files or folders which are publically accessible on site. Generally it includes javascripts, css and images used in the module.
    
    4: /src: contains submodules of the main module. It should always contain main module's logical structure (by default).
    4.1 : /src/CmsPage/: contains Controller, Models, Form etc logical scripts used in the module.
    4.1.1: /src/CmsPage/Controller: contains the controllers used in the module. In this module, we have following controllers:
    4.1.1(a): CmspageController.php: contains add, edit, view and delete actions.
    4.1.1(b): ViewcmspageController.php: contains view action which is used to display active cms pages in frontend.
    4.1.2: /src/CmsPage/Form: contains the forms which are used in this module. We have a form to add and edit the CMS page in admin section. It contains the required attributes of input fields used in a form and respective server side validations.
    4.1.3: /src/CmsPage/Model : contains model classes.
    4.1.3(a): Cmspage.php: contains the variable setter method and server side validations
    4.1.3(b): CmspageTable.php: contains the methods where zend queries are written and executed.
    4.1.4: /src/CmsPage/Service : contains classes with custom services. In this module, the service 'CmsPages.php' makes the cms list page easier to display in view file and is having special features of sort, search and visibility options.
    
    5: /view: contains all the view files used in the module. These view files are separated controller-wise.
    
    
    

