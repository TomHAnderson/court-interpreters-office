<?php
/**
 * module/Admin/src/Controller/ScheduleController.php
 */

namespace InterpretersOffice\Admin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Doctrine\ORM\EntityManagerInterface;
use InterpretersOffice\Entity;
use Laminas\Session\Container as Session;

/**
 * ScheduleController
 *
 */
class ScheduleController extends AbstractActionController
{
    /**
     * entityManager
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * session
     *
     * @var Laminas\Session\Container
     */
    protected $session;

    /**
     * configuration 
     * 
     * @var array
     */
    protected $config;

    /**
     * constructor
     *
     * @param EntityManagerInterface $e
     */
    public function __construct(EntityManagerInterface $e, array $config)
    {
        $this->entityManager = $e;
        $this->session = new Session('admin_schedule');
        $this->config = $config;
    
    }

    /**
     * index action
     *
     * @return mixed
     */
    public function scheduleAction()
    {

        $filters = $this->getFilters();
        $date = new \DateTime($filters['date']);
        $repo = $this->entityManager->getRepository(Entity\Event::class);
        $data = $repo->getSchedule($filters);
        $end_time_enabled = $this->config['end_time_enabled'];
        $viewModel = new ViewModel(compact('data', 'date','end_time_enabled'));
        $this->setPreviousAndNext($viewModel, $date)
            ->setVariable('language', $filters['language']);
        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewModel
                ->setTemplate('interpreters-office/admin/schedule/partials/table')
                ->setTerminal(true);
        }
        return $viewModel;
    }
    /**
     * gets date and language filters for schedule
     *
     * look first to GET parameters, then the session, otherwise
     * default to today's date and null language filter
     *
     * @return Array
     */
    public function getFilters()
    {
        $date_params = [];
        $params = $this->params()->fromRoute();
        foreach (['year','month','date'] as $p) {
            if (isset($params[$p])) {
                $date_params[$p] = $params[$p];
            }
        }
        if ($date_params) {
            $date = implode('-', $date_params);
            $this->session->date = $date;
        } elseif ($this->session->date) {
            $date = $this->session->date;
        } else { // default to today
            $date = date('Y-m-d');
        }
        $language = strtolower($this->params()->fromQuery('language'));
        if (! in_array($language, ['spanish','not-spanish','all'])) {
            $language = null;
        }
        if ($language) {
            $this->session->language = $language;
        } elseif ($this->session->language) {
            $language = $this->session->language;
        } else {
            $language = 'all';
        }

        return [ 'date' => $date, 'language' => $language];
    }

    /**
     * displays event details
     */
    public function viewAction()
    {
        $id = $this->params()->fromRoute('id');
        $data = $this->entityManager->getRepository(Entity\Event::class)
           ->getView($id);
        $event = $data['event'] ?? null;
        $csrf = (new \Laminas\Validator\Csrf('csrf', ['timeout' => 1200]))->getHash();
        $session = new \Laminas\Session\Container('event_updates');
        $before = null;
        if ($session->{$event['id']}) {
            $before = $session->{$event['id']};
            unset($session->{$event['id']});
        }
        $end_time_enabled = $this->config['end_time_enabled'];
        return compact('event', 'id', 'csrf', 'before','end_time_enabled');
    }

    /**
     * computes and sets the "next" and "previous" dates
     *
     * @param ViewModel $view
     * @param \DateTime  $date
     * @return ViewModel
     */
    public function setPreviousAndNext(ViewModel $view, \DateTime $date)
    {
        $string = $date->format('Y-m-d');
        $next = '+1';
        $prev = '-1';
        switch ($date->format('w')) {
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
        $view->prev = (new \DateTime("$string $prev days"))->format('/Y/m/d');
        $view->next = (new \DateTime("$string $next days"))->format('/Y/m/d');

        return $view;
    }
}
