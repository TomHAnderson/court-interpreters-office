<?php
/**
 * module/InterpretersOffice/src/Controller/IndexController.php.
 */

namespace InterpretersOffice\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\MvcEvent;
use Doctrine\ORM\EntityManager;
use InterpretersOffice\Admin\Service\ScheduleAccessAssertion;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use InterpretersOffice\Entity;
use Laminas\Session\Container as SessionContainer;

/**
 *  IndexController.
 */
class IndexController extends AbstractActionController implements ResourceInterface
{
    
    /**
     * entity manager
     * 
     * @var EntityManager
     */
    private $entityManager;
    
    /**
     * implements ResourceInterface
     *
     * @return string
     */
    public function getResourceId()
    {
         return self::class;
    }

    /**
     * onDispatch
     * 
     * @param MvcEvent
     */
    public function onDispatch(MvcEvent $e)
    {
        $app = $e->getApplication();
        $container = $app->getServiceManager();
        $log = $container->get('log');        
        $action = $this->params()->fromRoute()['action'];
        if (in_array($action,['schedule','view-event'])) {
            $acl =  $container->get('acl');
            $auth = $container->get('auth');
            $user = $auth->hasIdentity() ? $auth->getIdentity() : null;
            $log->debug("checking permissions for action $action");
            if (! $user) {
                $acl->allow(
                    $user, $this,$action, new ScheduleAccessAssertion($e)
                );
                $allowed = $acl->isAllowed($user,$this,$action);
                $log->debug("allowed?  ".($allowed?"true":"false"));
                if (!$allowed) { 
                    return $this->redirect()->toRoute('login');
                }
            }
        }
        return parent::onDispatch($e);
    }

    /**
     * constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    /**
     * index action.
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        return new ViewModel();
    }
  

    /**
     * schedule
     * 
     */
    public function scheduleAction()
    {
        /** @var InterpretersOffice\Entity\Repository\EventRepository */
        $repository = $this->entityManager->getRepository(Entity\Event::class);
        $session = new SessionContainer('schedule');
        $params = $this->params()->fromRoute();
        $opts = [];
        if (isset($params['date'])) { // then they all must be set
            $date_str = "{$params['year']}-{$params['month']}-{$params['date']}";
            $date = new \DateTime($date_str);
            $opts['date'] = $date;
            $session->date = $date;
        } elseif ($session->date) {
            $opts['date'] = $session->date;
        } else {
            $opts['date'] = new \DateTime();
            //$session->date = $date;
        }
        if (isset($params['language'])) {
            $session->language = $params['language'];
            $opts['language'] = $params['language'];

        } elseif ($session->language) {
            $opts['language'] = $session->language;
        } else {
            $opts['language'] = 'all';
        }
    
        $string = $opts['date']->format('Y-m-d');
        $next = '+1';
        $prev = '-1';
        switch ($opts['date']->format('w')) {
            case 0:
                $prev = '-2';
                break;
            case 1:
                $prev = '-3';
                break;
            case 5:
                $next = '+3';
                break;
            case 6:
                $prev = '-2';
                break;
        }
        $data = $repository->getSchedule($opts);
        // $data['date'] = $opts['date'];
        $data['prev'] = (new \DateTime("$string $prev days"))->format('/Y/m/d');
        $data['next'] = (new \DateTime("$string $next days"))->format('/Y/m/d');
        $view = new ViewModel(['data'=>$data,'date'=>$opts['date'],'language'=>strtolower($opts['language'])]);
        if ($this->getRequest()->isXmlHttpRequest()) {
            $view->setTemplate('partials/schedule-table')->setTerminal(true);
        }

        return $view;
    }



     /**
     * view event details
     * 
     */
    public function viewEventAction()
    {
        return false;
    }

    /**
     * a contact page
     *
     * @return ViewModel
     */
    public function contactAction()
    {
        return new ViewModel();
    }
}
