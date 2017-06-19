<?php

/** module/InterpretersOffice/src/Controller/Factory/AuthControllerFactory.php */

namespace InterpretersOffice\Controller\Factory;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Zend\Authentication\AuthenticationService;
use InterpretersOffice\Service\Authentication\Adapter as AuthAdapter;
use InterpretersOffice\Controller\AuthController;
use InterpretersOffice\Service\Listener\AuthenticationListener;

/**
 * Factory for instantiating AuthController.
 */
class AuthControllerFactory
{
    /**
     * implements FactoryInterface.
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array              $options
     *
     * @todo rethink this approach
     * 
     * @return AuthController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {        
        // attach event listeners
        $sharedEvents = $container->get('SharedEventManager');
        $listener = $container->get(AuthenticationListener::class);
        $sharedEvents->attach(
            $requestedName,
            'loginAction',
            [$listener, 'onLogin']
        );
        $sharedEvents->attach(
            $requestedName,
            'logoutAction',
            [$listener, 'onLogout']
        );
        $service = $container->get('auth');
        return new AuthController($service);
    }
}
