<?php 
/**  module/InterpretersOffice/src/Service/Listener/UserListener */

namespace InterpretersOffice\Service\Listener;

use Zend\EventManager\Event;
use Zend\Log\Logger;
use Doctrine\ORM\EntityManager;

/**
 * listener that observes authentication events.
 * 
 * maybe we will rename it to AuthenticationListener and create 
 * a different listener for user registration and password reset
 * events
 * 
 */ 

class UserListener
{

	/**
	 * log instance
	 * 
	 * @var Logger
	 */
	protected $log;

	/**
	 * entity manager
	 *
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * constructor
	 *
	 * @param Logger
	 * @param EntityManager
	 * 
	 */
	public function __construct(Logger $log, EntityManager $entityManager)
	{
		$this->log = $log;
		$this->entityManager = $entityManager;
	}

    /**
     * event handler for user login success or failure
     * 
     * @param Event 
     */
    public function onLogin(Event $e)
    {
        $params = $e->getParams();
        $result = $params['result'];
        $identity = $params['identity'];
        $ip = isset($_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'N/A';
        if ($result->isValid()) {
        	$message = sprintf(
	    		'user %s authenticated from IP address: %s',
	    		$identity, $ip
    		);	
			/**
			* @todo add last_login prop to entity ($result->getIdentity()) and update
    	 	*/
        } else {
        	$message = sprintf(
        		'login failed for user %s from IP address %s, reason: %s',
        		$identity, $ip, json_encode($result->getMessages())
        	);
        }
        $this->log->info($message);  	 
    }

    /**
     * event listener for user logout
     * 
     * @param Event 
     */
    public function onLogout(Event $e)
    {
    	$user = $e->getParam('user');
    	$message = sprintf('user %s logged out',$user->getPerson()->getEmail());
    	$this->log->info($message); 
    }
}
