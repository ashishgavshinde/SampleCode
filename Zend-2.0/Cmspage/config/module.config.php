<?php
/**
 * Module name: CMS page
 * File: Configuration file to define the view files path, invokable controllers, routing algorithms, javascript and css files
**/
return array(
    'view_manager' => array(
        'template_path_stack' => array(
        'cmspage' => __DIR__ . '/../view'
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Cmspage\Controller\Cmspage' => 'Cmspage\Controller\CmspageController'
        ),
    ),
    'service_manager' => array(
        'aliases' => array(
            'cmspage_zend_db_adapter' => 'Zend\Db\Adapter\Adapter',
        ),
    ),
    'router' => array(
        'routes' => array(
            'cmspage' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/cmspage[/:action][/:id][/:set-status]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9a-zA-Z]+',
                        'set-status'     => '[0-9a-zA-Z]+',
                        'statusvalue'  => '[0-9a-zA-Z]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Cmspage\Controller\Cmspage',
                        'action'     => 'index',
                    ),
                ),
            ),
            '[a-zA-Z][a-zA-Z0-9_-]*' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/[:page_name].html',
                    'constraints' => array(
                        'page_name' => '[a-zA-Z][a-zA-Z0-9_-]*'
                    ),
                    'defaults' => array(
                        'controller' => 'Cmspage\Controller\Viewcmspage',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'jsincludes' => array(
        'cmspage' => array('jquery-1.8.1.min.js','tiny_mce.js','tiny_mce_src.js','jquery.validate.min.js','cmspage.js','tinyEditor.js','alphanumeric.pack.js','thickbox.js')
    ),
    'cssincludes' => array(
        'cmspage' => array('thickbox.css')
    )
);
