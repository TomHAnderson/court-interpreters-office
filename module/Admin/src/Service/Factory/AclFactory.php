<?php
/**
 * module/Admin/src/Service/Factory/AclFactory.php.
 */

namespace InterpretersOffice\Admin\Service\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use InterpretersOffice\Admin\Service\Acl;
use Laminas\EventManager\EventManager;

/**
 * Factory for instantiating ACL service.
 */
class AclFactory implements FactoryInterface
{
    /**
     * implements FactoryInterface.
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array              $options
     *
     * @return Acl
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['acl'];
        $log = $container->get('log');
        $auth = $container->get('auth');
        $acl = new Acl($config);
        $sharedEventManager = $container->get('SharedEventManager');
        $sharedEventManager->attach(
            Acl::class,
            'access-denied',
            function ($e) use ($log, $auth) {
                $identity = $auth->getIdentity() ;
                $role = $e->getParam('role', '');
                $resource = $e->getParam('resource', '');
                $message = sprintf(
                    "access DENIED to user %s in role %s: resource %s; action %s",
                    $identity ? $identity->email : 'anonymous',
                    is_string($role) ? $role : $role->getRoleId(),
                    is_string($resource) ? $resource : $resource->getResourceId(),
                    $e->getParam('privilege', 'N/A')
                );
                 $log->info($message, ['channel' => 'security']);
            }
        );
       // note to self: if $acl implements EventManagerAwareInterface,
       // it seems we don't have to do the following ourselves
       // $acl->setEventManager(new EventManager($sharedEventManager));
        return $acl;
    }
}
