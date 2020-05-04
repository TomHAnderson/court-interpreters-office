<?php
/**
 * module/InterpretersOffice/src/Service/Factory/UserListenerFactory.php.
 */

namespace InterpretersOffice\Service\Factory;

/* module/InterpretersOffice/src/Factory/UserListenerFactory */

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use InterpretersOffice\Service\Listener\AuthenticationListener;

/**
 * Factory for instantiating user listener service.
 */
class AuthenticationListenerFactory implements FactoryInterface
{
    /**
     * implements FactoryInterface.
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array              $options
     *
     * @return AuthenticationListener
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['security'] ?? [];
        $max_login_failures = $config['max_login_failures'] ?? 6;

        return new AuthenticationListener(
            $container->get('log'),
            $container->get('entity-manager'),
            $max_login_failures
        );
    }
}
