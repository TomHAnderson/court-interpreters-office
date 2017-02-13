<?php
/**
 * module/Admin/src/Service/Acl.php
 */
namespace InterpretersOffice\Admin\Service;

use Zend\Permissions\Acl\Acl as ZendAcl;

use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManagerAwareInterface;

/**
 * ACL
 * 
 * doesn't seem to be necessary to implement EventManagerAwareInterface
 * explicitly if we call setEventManager on this ourselves
 * 
 */
class Acl extends ZendAcl implements EventManagerAwareInterface {
    
    use EventManagerAwareTrait;
    /**
     * configuration
     * 
     * @var Array
     */
    protected $config;
    
    /*
     * event manager
     * .... or not. seems to make PHP 5.6 sad.
     * @var EventManagerInterface $events
     */
    // protected $events;
    
    /**
     * constructor
     * 
     * @param array $config
     */
    public function __construct(Array $config)
    {
       $this->config = $config; 
       $this->setup();
    }
    
    /**
     * initialize the ACL
     */
    protected function setup()
            
    {       
        foreach($this->config['resources'] as $resource => $parent) {
            $this->addResource($resource, $parent);
        }
        foreach ($this->config['roles'] as $role => $parents) {
            $this->addRole($role,$parents);
        }
        /*
        'allow' => [
            //'role' => [ 'resource' => [ priv, other-priv, ...  ]
            'submitter' => [
                'requests' => ['create','view','index'],
                'events'   => ['index','view','search'],
            ],
            'manager' => [                
                'languages' => null,
                'events' => null,
            ],
            'administrator' => null,
        ],
         */
        foreach($this->config['allow'] as $role => $rules ) {
           if (null === $rules) {
               $this->allow($role);
               continue;
           }
           foreach($rules as $resource => $privileges) {
               //printf ("we are setting allow on role %s, resource %s, privs %s<br>",$role,$resource, is_scalar($privileges)
               // ? $privileges : implode(",",$privileges));
               $this->allow($role,$resource,$privileges);               
           }            
        }
        foreach($this->config['deny'] as $role => $rules ) {
           if (null === $rules) {
               $this->deny($role);
               continue;
           }
           foreach($rules as $resource => $privileges) {
               //printf ("we are setting allow on role %s, resource %s, privs %s<br>",$role,$resource, is_scalar($privileges)
               // ? $privileges : implode(",",$privileges));
               $this->deny($role,$resource,$privileges);               
           }            
        } 
    }
    
    /**
     * 
     * inherited from Zend\Permissions\Acl\Acl
     * 
     * @return boolean if authorized 
     */
    public function isAllowed($role = null, $resource = null, $privilege = null) {
        $allowed = parent::isAllowed($role, $resource, $privilege);
        if (! $allowed) {
            $this->events->trigger('access-denied', $this, \compact('role','resource','privilege'));
        }
        return $allowed;
    }
}
