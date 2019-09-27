<?php
/** config/autoload/navigation.global.php
 *
 * still a work in progress
 */
use InterpretersOffice\Controller as Main;

use InterpretersOffice\Admin\Controller as Admin;

return [

    'navigation' => [
        // breadcrumbs navigation helper
        'admin_breadcrumbs' =>
        [
            [
                'label' => 'admin',
                'route' => 'admin',
                'pages' => [
                    [
                        'label' => 'schedule',
                        'route' => 'events',
                        'pages' => [
                            [
                                'label' => 'add',
                                'route' => 'events/add'
                            ],
                            [
                                'label' => 'edit',
                                'route' => 'events/edit'
                            ],
                        ],
                    ],
                    [
                        'label' => 'languages',
                        'route' => 'languages',
                        'pages' => [
                            [
                                'label' => 'add',
                                'route' => 'languages/add'
                            ],
                            [
                                'label' => 'edit',
                                'route' => 'languages/edit',
                            ]
                        ]
                    ],
                    [
                        'label' => 'people',
                        'route' => 'people',
                        'pages' => [
                            [
                            'label' => 'add',
                            'route' => 'people/add'
                            ],
                            [
                            'label' => 'edit',
                            'route' => 'people/edit',
                            ],
                            [
                            'label' => 'view details',
                            'route' => 'people/view',
                            ],
                            [
                                'label' => 'interpreters',
                                'route' => 'interpreters',
                                //'resource' => Admin\InterpretersController::class,
                                'pages' => [
                                    [
                                        'label' => 'add',
                                        'route' => 'interpreters/add'
                                    ],
                                    [
                                        'label' => 'edit',
                                        'route' => 'interpreters/edit',
                                    ],
                                    [
                                        'label' => 'view details',
                                        'route' => 'interpreters/find_by_id',
                                    ],
                                    [
                                        'label' => 'search',
                                        'route' => 'interpreters/find_by_language',
                                    ],
                                    [
                                        'label' => 'search',
                                        'route' => 'interpreters/find_by_name',
                                    ],
                                ],
                            ],
                            [
                                'label' => 'judges',
                                'route' => 'judges',
                                'pages' => [
                                    [
                                        'label' => 'add',
                                        'route' => 'judges/add'
                                    ],
                                    [
                                        'label' => 'edit',
                                        'route' => 'judges/edit'
                                    ],
                                    [
                                        'label' => 'view details',
                                        'route' => 'judges/view'
                                    ],
                                ]
                            ],
                            [
                                'label' => 'users',
                                'route' => 'users',
                                'pages' => [
                                    [
                                        'label' => 'add',
                                        'route' => 'users/add'
                                    ],
                                    [
                                        'label' => 'edit',
                                        'route' => 'users/edit'
                                    ],
                                    [
                                        'label' => 'view details',
                                        'route' => 'users/view'
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'label' => 'event-types',
                        'route' => 'event-types',
                        'pages' => [
                            [
                                'label' => 'add',
                                'route' => 'event-types/add'
                            ],
                            [
                                'label' => 'edit',
                                'route' => 'event-types/edit'
                            ],
                        ],
                    ],
                    [
                        'label' => 'search',
                        'route' => 'search',
                    ],
                    [
                        'label' => 'locations',
                        'route' => 'locations',
                        'pages' => [
                            [
                                'label' => 'add',
                                'route' => 'locations/add'
                            ],
                            [
                                'label' => 'edit',
                                'route' => 'locations/edit'
                            ],
                        ]
                    ],
                    [
                        'label' => 'defendants',
                        'route' => 'admin-defendants',
                        'pages' => [
                            [
                                'label' => 'add',
                                'route' => 'admin-defendants/add'
                            ],
                            [
                                'label' => 'edit',
                                'route' => 'admin-defendants/edit'
                            ],
                        ]
                    ],
                    [
                        'label' => 'court closings',
                        'route' => 'court-closings',
                        'pages' => [
                            [
                                'label' => 'add',
                                'route' => 'court-closings/add'
                            ],
                            [
                                'label' => 'edit',
                                'route' => 'court-closings/edit'
                            ],
                        ]
                    ]
                ],
            ],
        ],
        // main navigation menu
        'default' => [
            [
                'label' => 'admin',
                'route' => 'admin',
                'title' => 'main admin page',
                'resource' => Admin\IndexController::class,
            ],
            [
                'label' => 'schedule',
                'title' => 'view the interpreters\' schedule',
                'route' => 'events',
                'route_matches'=>[
                    'events',
                    'events/display',
                    'events/edit',
                ],
                'resource' => Admin\EventsController::class,

            ],
            [
                'label' => 'add event',
                'route' => 'events/add',
                'route_matches'=>[
                    'events/add',
                ],
                'title' => 'create a new event on the schedule',
                'resource' => Admin\EventsController::class,
                'privilege' => 'add',
            ],
            [
                'label' => 'search',
                'route' => 'search',
                'resource' => Admin\SearchController::class,
                'css_class' => 'd-none d-xl-inline',

            ],
            [
                'label' => 'interpreters',
                'route' => 'interpreters',
                'route_matches'=>[
                    'interpreters/find_by_language',
                    'interpreters/find_by_name',
                ],
                'title' => 'manage the roster of interpreters',
                'resource' => Admin\InterpretersController::class,
                //'order' => 6000,
            ],
            [
                'label' => 'other data',
                'route' => 'admin',
                'title' => 'manage other data entities',
                'resource' => Admin\IndexController::class,
                'order' => 600,
                'pages' => [
                    [
                        'label' => 'languages',
                        'route' => 'languages',
                        'route_matches'=>['languages/edit','languages/add'],
                    ],
                    [
                        'label' => 'locations',
                        'route' => 'locations',
                        'route_matches'=>['locations/edit','locations/add'],
                    ],
                    [
                        'label' => 'judges',
                        'route_matches'=>['judges/edit','judges/add'],
                        'route' => 'judges',
                    ],
                    [
                        'label' => 'users',
                        'route' => 'users',
                        'route_matches'=>['users/edit','users/add'],

                    ],
                    [
                        'label' => 'people',
                        'route' => 'people',
                        'route_matches'=>['people/edit','people/add'],
                        'resource' => Admin\PeopleController::class,
                    ],
                    [
                        'resource' => Admin\EventTypesController::class,
                        'label' => 'event-types',
                        'route_matches'=>['event-types/edit','event-types/add',],
                        'route' => 'event-types',

                    ],
                    [
                        'label' => 'defendants',
                        'route' => 'admin-defendants',
                        'route_matches'=>[
                             'admin-defendants',
                        ],
                        'foo'  => 'boink',
                        'resource' => Admin\DefendantsController::class,
                    ],
                    [
                        'resource' => Admin\CourtClosingsController::class,
                        'label' => 'court closings',
                        'route' => 'court-closings',
                        'route_matches'=>[
                              'court-closings',
                        ],
                    ],
                ],
            ],
            'tools'=>
            [
                'label' => 'tools',
                'title' => 'yadda',
                'order' => 700,
                'uri' => '/',
                //'resource' => Admin\EventsController::class,
                'pages' => [
                    [
                        'label' => 'search',
                        'route' => 'search',
                        //'order' => 50000,
                        //'css_class' => 'd-none d-sm-block',
                    ],
                    [
                        'label' => 'reports',
                        'uri' => '#',
                        //'order' => 20,
                    ],
                    [
                        'label' => 'email',
                        'route' => 'email/templates',
                        'resource' => Admin\EmailController::class,
                    ],
                    [
                        'label' => 'help',
                        'uri' => '#',
                    ],
                ]
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'navigation' => Zend\Navigation\Service\DefaultNavigationFactory::class,
        ],
    ],
];
