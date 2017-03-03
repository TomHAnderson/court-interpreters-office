<?php
/**
 * module/InterpretersOffice/src/Entity/Listener/Factory/UpdateListenerFactory.php
 */

namespace InterpretersOffice\Entity\Listener\Factory;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use InterpretersOffice\Entity\Listener\UpdateListener;

/**
 * Factory for instantiating Entity\UpdateListener
 */
class UpdateListenerFactory implements FactoryInterface {
    
    /**
     * time to get invoked!
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array              $options
     *
     * @return ExampleController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // constructor arguments subject to change. for now, this is handy
        // for debugging.
        $listener = new UpdateListener(
            $container->get('log')
        );

        return $listener;
    }
    
}
