<?php
/**
 * module/InterpretersOffice/src/Entity/Listener/Factory/InterpreterEntityListenerFactory.php
 */

namespace InterpretersOffice\Entity\Listener\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

use InterpretersOffice\Entity\Listener\InterpreterEntityListener;
use SDNY\Vault\Service\Vault;

/**
 * InterpreterEntityListenerFactory
 *
 */
class InterpreterEntityListenerFactory implements FactoryInterface
{

    /**
     * instantiates Interpreter entity listener
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array              $options
     *
     * @return InterpreterEntityListener
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $listener = new InterpreterEntityListener();
        $listener->setLogger($container->get('log'));
        $config = $container->get('config');
        $vault_config = isset($config['vault']) ? $config['vault'] : ['enabled' => false];
        if ($vault_config['enabled']) {
            $listener->setVaultService($container->get(Vault::class));
        }
        return $listener;
    }
}
