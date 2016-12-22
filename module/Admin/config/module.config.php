<?php

/**
 * configuration for InterpretersOffice\Admin module.
 */

namespace InterpretersOffice\Admin;

use Zend\Router\Http\Segment;

return [

    'router' => [
        'routes' => [

            'admin' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/admin',//[/]
                    'defaults' => [
                        'module' => __NAMESPACE__,
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'languages' => [
                'type' => Segment::class,
                'may_terminate' => true,
                'options' => [
                    'route' => '/admin/languages',
                    'defaults' => [
                        'module' => __NAMESPACE__,
                        'controller' => Controller\LanguagesController::class,
                        'action' => 'index',
                    ],
                ],
                'child_routes' => [
                    'add' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/add',
                            'defaults' => [
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action/:id',
                            'defaults' => [
                                'action' => 'edit',

                            ],
                            'constraints' => [
                                'action' => 'edit|delete',
                                'id' => '[1-9]\d*',
                            ],
                        ],
                    ],
                ],
            ],
            'locations' => [
                'type' => Segment::class,
                'may_terminate' => true,
                'options' => [

                    'route' => '/admin/locations',
                    'defaults' => [
                        'module' => __NAMESPACE__,
                        'controller' => Controller\LocationsController::class,
                        'action' => 'index',
                    ],
                ],
                'child_routes' => [
                    'type' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/type/:id',
                            'defaults' => [
                                'action' => 'index',
                            ],
                            'constraints' => [
                                'id' => '[1-9]\d*',
                            ],
                        ],
                    ],
                    'add' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/add',
                            'defaults' => [
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action/:id',
                            'defaults' => [
                                'action' => 'edit',

                            ],
                            'constraints' => [
                                'action' => 'edit|delete',
                                'id' => '[1-9]\d*',
                            ],
                        ],
                    ],
                ],
            ],
            'event-types' => [
                'type' => Segment::class,
                'may_terminate' => true,
                'options' => [
                    'route' => '/admin/event-types',
                    'defaults' => [
                        'module' => __NAMESPACE__,
                        'controller' => Controller\EventTypesController::class,
                        'action' => 'index',
                    ],
                ],
                'child_routes' => [
                    'add' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/add',
                            'defaults' => [
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action/:id',
                            'defaults' => [
                                'action' => 'edit',

                            ],
                            'constraints' => [
                                'action' => 'edit|delete',
                                'id' => '[1-9]\d*',
                            ],
                        ],
                    ],
                ],
            ],
            'people' => [
                'type' => Segment::class,
                'may_terminate' => true,
                'options' => [
                    'route' => '/admin/people',
                    'defaults' => [
                        'module' => __NAMESPACE__,
                        'controller' => Controller\PeopleController::class,
                        'action' => 'index',
                    ],
                ],
                'child_routes' => [
                    'add' => [  
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/add',
                            'defaults' => [
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action/:id',
                            'defaults' => [
                                'action' => 'edit',

                            ],
                            'constraints' => [
                                'action' => 'edit|delete',
                                'id' => '[1-9]\d*',
                            ],
                        ],
                    ],
                ],
            ],
            'judges' => [
                'type' => Segment::class,
                'may_terminate' => true,
                'options' => [
                    'route' => '/admin/judges',
                    'defaults' => [
                        'module' => __NAMESPACE__,
                        'controller' => Controller\JudgesController::class,
                        'action' => 'index',
                    ],
                ],
                'child_routes' => [
                    'add' => [  
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/add',
                            'defaults' => [
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/:action/:id',
                            'defaults' => [
                                'action' => 'edit',

                            ],
                            'constraints' => [
                                'action' => 'edit|delete',
                                'id' => '[1-9]\d*',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [

        'invokables' => [
            Controller\IndexController::class => Controller\IndexController::class,
        ],
        'factories' => [
            Controller\LanguagesController::class  => Controller\Factory\SimpleEntityControllerFactory::class,
            Controller\LocationsController::class  => Controller\Factory\SimpleEntityControllerFactory::class,
            Controller\EventTypesController::class => Controller\Factory\SimpleEntityControllerFactory::class,
            Controller\PeopleController::class     => Controller\Factory\PeopleControllerFactory::class,
            Controller\JudgesController::class     => Controller\Factory\PeopleControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'interpreters-office/admin/index/index' => __DIR__.'/../view/interpreters-office/admin/index/index.phtml',
        ],
        'template_path_stack' => [
            'interpreters-office/admin' => __DIR__.'/../view',
        ],
    ],

];
