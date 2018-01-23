<?php
/**
 * module/Admin/src/Controller/LanguagesController.php.
 */

namespace InterpretersOffice\Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Doctrine\ORM\EntityManagerInterface;
use Zend\Authentication\AuthenticationServiceInterface;

use Zend\EventManager\Event;

use InterpretersOffice\Admin\Form;

use InterpretersOffice\Entity;

/**
 *  EventsController
 *
 */

/* some handy SQL
 SELECT e.id, e.date, e.time, t.name type, l.name language, 
 COALESCE(j.lastname, a.name) AS judge, p.name AS place, 
 COALESCE(s.lastname,as.name) submitter, submission_datetime FROM events e 
 JOIN event_types t ON e.event_type_id = t.id 
 JOIN languages l ON e.language_id = l.id 
 LEFT JOIN people j ON j.id = e.judge_id 
 LEFT JOIN anonymous_judges a ON a.id = e.anonymous_judge_id 
 LEFT JOIN people s ON e.submitter_id = s.id
 LEFT JOIN hats AS `as` ON e.anonymous_submitter_id = as.id
 LEFT JOIN locations p ON e.location_id = p.id;
 */

/**
 * events controller
 * 
 * controller for inserting and updating court interpreting events
 * 
 */
class EventsController extends AbstractActionController
{
    
    /**
     * entity manager
     * 
     * @var EntityManagerInterface
     */
    protected $entityManager;
    
    /**
     * authentication service
     * 
     * @var AuthenticationServiceInterface 
     */
    protected $auth;

    /**
     * view
     * 
     * @var ViewModel
     */
    protected $viewModel;
    
    /**
     * constructor
     *
     * @param EntityManagerInterface $em
     * @param AuthenticationServiceInterface $auth
     */
    public function __construct(EntityManagerInterface $em, 
            AuthenticationServiceInterface $auth)
    {
        $this->entityManager = $em;
        $this->auth = $auth;
    }
    
    /**
     * lazy-instantiates and returns ViewModel
     * 
     * @param array $data view variables to set
     * @return ViewModel
     */    
    public function getViewModel(Array $data = [])
    {
        if (! $this->viewModel) {

            $this->viewModel = new ViewModel();
            $this->viewModel
                ->setTemplate('interpreters-office/admin/events/form');
            if ($data) {
                $this->viewModel->setVariables($data);
            }
        }

        return $this->viewModel;
    }
    
    /**
     * index action
     *
     */
    public function indexAction()
    {        
        return ['title' => 'schedule'];
    }

    /**
     * adds a new event
     */
    public function addAction()
    {
        $form = new Form\EventForm(
            $this->entityManager,
            [   'action' => 'create',
                'auth_user_role'=> $this->auth->getIdentity()->role,
                'object' => null,
            ]
        );
        $form->attach($this->getEventManager());
        
        $request = $this->getRequest();
        $form->setAttribute('action', $request->getRequestUri());
        $event = new Entity\Event();
        $form->bind($event);
        // test
        if (false) {
            $shit = $form->get("event");
            $shit->get("date")->setValue('10/27/2017');
            $shit->get("time")->setValue('10:00 am');
            $shit->get("judge")->setValue(948);
            $shit->get("language")->setValue(62);
            $shit->get("parent_location")->setValue(6);
            $shit->get("location")->setValue(11);
            $shit->get("eventType")->setValue(1);
            $shit->get("docket")->setValue("2016-CR-0345");
            $shit->get("anonymousSubmitter")->setValue("6");
            $shit->get("submission_date")->setValue("10/24/2017");
            $shit->get("submission_time")->setValue("10:17 am");
            $shit->get("submission_datetime")->setValue('2017-10-24 10:17:00');
        // end test
        }
        $viewModel = $this->getViewModel()->setVariables(['form'  => $form,]);
        
        if ($request->isPost()) {
            $data = $request->getPost();
            $input = $data->get('event');
            $this->getEventManager()->trigger('pre.validate',$this,
                ['input'=> $data,]);              
            $form->setData($data);            
            if (! $form->isValid()) {                
                if ($input) {
                    $defendantNames = isset($input['defendantNames']) ? 
                        $input['defendantNames'] : [];
                    $interpreters = isset($input['interpreterEvents']) ? 
                        $input['interpreterEvents'] : [];
                }
                return $viewModel
                    ->setVariables(compact('defendantNames','interpreters'));
            } else {              
                //printf('<pre>%s</pre>', print_r($data, true)); //return;
                //$fuckingShit = $form->getData();
                //var_dump($fuckingShit->getDate());
                //var_dump($fuckingShit->getSubmissionDate());
                //exit();
                //$events = $this->getEventManager();
                //$form->attach($events);
                $this->entityManager->persist($event);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage(//echo gettype($value) . " is the data type of $time<br>";
                     "This event has been added to the schedule.");                
                return $this->redirect()->toRoute('events');
            }            
        }
        
        return $viewModel;
    }
    
    
    /**
     * edits an court interpreting event
     *
     */
    public function editAction()
    {
        $id = $this->params()->fromRoute('id');        
        $entity = $this->entityManager->find(Entity\Event::class,$id);        
        if (! $entity) {
             return $this->getViewModel([               
                'errorMessage'  => 
                 "event with id $id was not found in the database." ]);
        }                
        $form = new Form\EventForm(
            $this->entityManager, ['action' => 'update','object'=>$entity,]
        );
        $events = $this->getEventManager();
        $form->attach($events);
        $events->trigger('post.load',$this,['entity'=>$entity]);
        $request = $this->getRequest();
        $form->bind($entity);                
        $events->trigger('pre.populate');
        
        if ($request->isPost()) {            
            $data = $request->getPost();            
            $input = $data->get('event'); //var_dump($input);
            $events->trigger('pre.validate',$this);  
            $form->setData($data);
            if ($form->isValid()) {
                $events->trigger('post.validate',$this);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage(
                     "This event has been successfully saved in the database.");                
                return $this->redirect()->toRoute('events');
            }
            //printf('<pre>error:  %s</pre>',print_r($form->getMessages(),true));
            if ($form->hasTimestampMismatchError()) {
                $error = $form->getMessages()['modified']
                        [\Zend\Validator\Callback::INVALID_VALUE];
                $this->flashMessenger()->addErrorMessage($error);                     
                return $this->redirect()
                        ->toRoute('events/edit',['id'=>$id]);
            }
            /** @todo put this task in the 'pre.validate' event listener ? */
            if ($input) {
                $defendantNames = isset($input['defendantNames']) ? 
                        $input['defendantNames'] : [];
                $interpreters = isset($input['interpreterEvents']) ? 
                        $input['interpreterEvents'] : [];
                $form->get('event')->get('anonymousSubmitter')
                    ->setValue($input['anonymousSubmitter']);
                $this->getViewModel()
                ->setVariables(compact('defendantNames','interpreters','form'));
            }                                        
        } 
        return $this->getViewModel(['form'=>$form]);
    }

    
    /**
     * generates markup for an interpreter
     * 
     * @return Zend\Http\PhpEnvironment\Response
     * @throws \RuntimeException
     */
    public function interpreterTemplateAction()
    {
        $helper = new Form\View\Helper\InterpreterElementCollection();
        $factory = new \Zend\InputFilter\Factory();
        $inputFilter = $factory->createInputFilter(                
            $helper->getInputFilterSpecification()
        );
        $data = $this->params()->fromQuery();
        $inputFilter->setData($data);
        if (! $inputFilter->isValid()) {
            throw new \RuntimeException(
                "bad input parameters: "
                    .json_encode($inputFilter->getMessages(),\JSON_PRETTY_PRINT)
            );
        }
        $data['created_by'] =  $this->auth->getStorage()->read()->id;
        $html = $helper->fromArray($data);
        return $this->getResponse()->setContent($html);
    }
    
    
    /**
     * gets interpreter options for populating select
     * 
     * @return JsonModel
     */
    public function interpreterOptionsAction()
    {
        /** @var  \InterpretersOffice\Entity\Repository\InterpreterRepository $repository */
        $repository = $this->entityManager->getRepository(Entity\Interpreter::class);        
        $language_id = $this->params()->fromQuery('language_id');
        if (! $language_id) {
            $result = ['error' => 'missing language id parameter'];
        } else {
            $result = $repository->getInterpreterOptionsForLanguage($language_id);
        }
        return new JsonModel($result);        
    }
}
