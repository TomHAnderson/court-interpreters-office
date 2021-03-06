<?php
/** module/Admin/src/Controller/UsersController.php */

namespace InterpretersOffice\Admin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\EventInterface;
use Laminas\Authentication\AuthenticationService;
use Laminas\View\Model\JsonModel;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Session\Container as Session;

use InterpretersOffice\Admin\Form\UserForm;
use InterpretersOffice\Entity;
use InterpretersOffice\Service\Authentication\AuthenticationAwareInterface;
use InterpretersOffice\Admin\Service\Acl;
use InterpretersOffice\Entity\Hat;
use InterpretersOffice\Entity\Repository\UserRepository;

/**
 * controller for admin/users.
 *
 *
 */
class UsersController extends AbstractActionController implements AuthenticationAwareInterface
{
    /**
     * entity manager.
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * the role of the currently authenticated user
     * @var string $auth_user_role
     */
    protected $auth_user_role;

    /**
     * acl
     *
     * @var Acl
     */
    protected $acl;

    /**
     * session
     *
     * @var Laminas\Session\Container
     */
    protected $session;

    /**
     * constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param AuthenticationServiceInterface $auth
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        AuthenticationServiceInterface $auth
    ) {
        $this->entityManager = $entityManager;
        $this->setAuthenticationService($auth);
    }

    /**
     * implements AuthenticationAwareInterface
     *
     * @param AuthenticationService $auth
     */
    public function setAuthenticationService(AuthenticationService $auth)
    {
        $this->auth = $auth;
        $this->auth_user_role = $auth->getIdentity()->role;
    }

   /**
    * attaches event handlers
    *
    * Before they try to create a user account, if they're attaching the account
    * to a Person that already exists, this checks the Person's Hat property for
    * validity. We won't expose links that lead to an invalid situation, but a
    * person could manually edit the query parameters.
    *
    * If they do load a valid Person in the addAction(), we check that an
    * associated User account does not already exist, and whether the current
    * user is authorized.
    *
    *
    * @param EventManagerInterface events
    */
    public function setEventManager(EventManagerInterface $events)
    {

        $entityManager = $this->entityManager;
        $role_id = $this->auth_user_role;
        // echo " the fuck? ... not sure why this runs twice";
        $events->attach('load-person', function (EventInterface $e) use ($entityManager, $role_id) {
            $person = $e->getParam('person');
            $hat = $person->getHat();
            $form = $e->getParam('form');
            $hat_options = $form->get('user')->get('person')->get('hat')
                    ->getValueOptions();
            $hats_allowed = array_column($hat_options, 'value');
            if (! in_array($hat->getId(), $hats_allowed)) {
                $message = sprintf(
                    'The person identified by id %d, %s %s, wears the hat %s, '
                    . 'but people in that category do not have user accounts '
                    . 'in this system.',
                    $person->getId(),
                    $person->getFirstName(),
                    $person->getLastname(),
                    $hat
                );
                $controller = $e->getTarget();
                $controller->flashMessenger()->addErrorMessage($message);
                return $controller->redirect()->toRoute('users');
            }

            /**
             * this needs work. there are rare cases where one and the same
             * person may legitimately have to have more than one user account,
             * only one of which can be active at any one time, and each having
             * a different role from the other:  'submitter' vs any of the other
             * roles.
             */
            $user = $entityManager->getRepository('InterpretersOffice\Entity\User')
                    ->findOneBy(['person' => $person]);
                    //->getUser(['entity'=>'person','id' => $person->getId()]);
            if ($user) {
                $container = $e->getTarget()->getEvent()->getApplication()
                        ->getServiceManager();

                $message = sprintf(
                    'We can\'t create a new user account because this person '
                    . ' (%s %s, id %d) already has one. ',
                    $person->getFirstname(),
                    $person->getLastname(),
                    $person->getId()
                );
                $acl = $e->getTarget()->getEvent()->getApplication()
                                ->getServiceManager()->get('acl');
                if ($acl->isAllowed($role_id, $user->getResourceId())) {
                    $helper = $container->get('ViewHelperManager')->get('url');
                    $url = $helper('users/edit', ['id' => $user->getId()]);
                    $message .= sprintf(
                        'You can <a href="%s">edit it</a> if you want to.',
                        $url
                    );
                }
                $controller = $e->getTarget();
                $controller->flashMessenger()->addErrorMessage($message);
                return $controller->redirect()->toRoute('users');
            }
        });
        // are they authorized to edit this user account?
        $events->attach('load-user', function (EventInterface $e) use ($role_id) {
            $resource_id = $e->getParam('user')->getResourceId();
            $acl = $e->getTarget()->getEvent()->getApplication()
                            ->getServiceManager()->get('acl');
            if (! $acl->isAllowed($role_id, $resource_id)) {
                $controller = $e->getTarget();
                $message = "Access denied to {$resource_id}'s user account";
                $controller->flashMessenger()->addErrorMessage($message);
                return $controller->redirect()->toRoute('users');
            } else {
                $services = $e->getTarget()->getEvent()->getApplication()
                                ->getServiceManager();
                $services->get(Entity\Listener\UpdateListener::class)
                    ->setAuth($this->auth);
            }
        });

        return parent::setEventManager($events);
    }

    /**
     * finds an existing person by email address
     *
     * @return JsonModel
     */
    public function findPersonAction()
    {
        $email = $this->params()->fromQuery('email');
        $hat = $this->params()->fromQuery('hat');
        if (! $email or ! $hat) {
            return new JsonModel([
                'status' => 'error',
                'valid'  => false,
                'message' => 'missing parameters',
            ]);
        }

        $validator = new \Laminas\Validator\EmailAddress();
        $valid = $validator->isValid($email);
        if (! $valid) {
            return new JsonModel([
                'status' => 'error',
                'valid' => false,
                'message' => 'invalid email address',
            ]);
        }
        // try to locate the person by email address
        $repo = $this->entityManager
            ->getRepository('InterpretersOffice\Entity\Person');
        $people = $repo->findPersonByEmail($email);
        return new JsonModel([
            'valid' => true,
            'status' => 'success',
            'result' => $people,
        ]);
    }

    /**
     * adds a new user
     */
    public function addAction()
    {
        $viewModel = new ViewModel(['title' => 'add a user']);
        // if they are trying to add a user account for an existing person...
        $person_id = $this->params()->fromRoute('id');
        // or if there's no person_id route parameter...
        if (! $person_id && isset($this->params()->fromPost()['user'])) {
            // try post parameters
            $user = $this->params()->fromPost()['user'];
            if (isset($user['person']) && ! empty($user['person']['id'])) {
                $person_id = $user['person']['id'];
            }
        }
        $options = [
            'action' => 'create',
            'auth_user_role' => $this->auth_user_role,
            'constrain_email' => true,
        ];

        if ($person_id) {
            /** @todo better db efficiency... */
            $person = $this->entityManager
                    ->find('InterpretersOffice\Entity\Person', $person_id);
            if (! $person) {
                return $viewModel->setVariables(
                    ['errorMessage' => "person with id $person_id not found"]
                );
            }
            $options['existing_person'] = $person;
        } else {
            $person = null;
        }
        $form = new UserForm($this->entityManager, $options);
        $user = new Entity\User();
        if ($person) {
            $this->events->trigger(
                'load-person',
                $this,
                compact('person', 'form')
            );
            $user->setPerson($person);
            $form->get('user')->get('person')->setObject($person);
        }

        $form->bind($user);
        $viewModel->form = $form;
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());
            if (! $form->isValid()) {
                return new JsonModel(['status' => 'error',
                'validation_errors' => $form->getMessages()]);
            }
            $user->setCreated(new \DateTime());
            $this->entityManager->persist($user);
            if (! $person_id) {
                $this->entityManager->persist($user->getPerson());
            }
            // we could do this in the model instead, with lifecycle callback (?)
            $user->setPassword(bin2hex(openssl_random_pseudo_bytes(8)));
            $this->entityManager->flush();
            $person = $user->getPerson();
            $this->flashMessenger()->addSuccessMessage(
                sprintf(
                    'A user account has been created for %s <strong>%s %s</strong>.',
                    $user->getRole(),
                    $person->getFirstName(),
                    $person->getLastname()
                )
            );
            return new JsonModel(['status' => 'success','validation_errors' => null]);
        }

        return $viewModel;
    }

    /**
     * edits an existing user account
     */
    public function editAction()
    {

        $id = $this->params()->fromRoute('id');
        $viewModel = new ViewModel(['title' => 'edit a user','id' => $id]);
        $entity = $this->params()->fromRoute('entity', 'user');
        /** @var InterpretersOffice\Entity\Repository\UserRepository $repo */
        $repo = $this->entityManager->getRepository('InterpretersOffice\Entity\User');
        $user = $repo->getUser($id, $entity);
        if (! $user) {
            return $viewModel->setVariables(['errorMessage' =>
                "user with id $id was not found in your database."]);
        }
        $this->getEventManager()->trigger('load-user', $this, ['user' => $user,]);
        $form = new UserForm($this->entityManager, [
            'action' => 'update',
            'auth_user_role' => $this->auth_user_role,
            'user' => $user,'constrain_email' => true,
            ]);
        /** @var $person \InterpretersOffice\Entity\Person */
        $person = $user->getPerson();

        /** @todo do this initialization somewhere else?  */
        $form->get('user')->get('person')->setObject($person);
        /* -------------------------- */
        $viewModel->form = $form;
        $has_related_entities = $this->entityManager
            ->getRepository(Entity\Person::class)
            ->hasRelatedEntities($person->getId());
        $viewModel->has_related_entities = $has_related_entities;

        if ($has_related_entities) {
            $user_input = $form->getInputFilter()->get('user');
            $user_input->get('person')->get('hat')->setRequired(false);
            $user_input->get('role')->setRequired(false);
        }
        $form->bind($user);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $was_disabled = ! $user->isActive();
            $form->setData($request->getPost());
            if (! $form->isValid()) {
                return new JsonModel(['status' => 'error',
                'validation_errors' => $form->getMessages()]);
            }
            // if they re-enabled the account
            if ($was_disabled && $user->isActive()) {
                $user->setFailedLogins(0);
            }
            $this->entityManager->flush();
            $this->flashMessenger()
                ->addSuccessMessage(sprintf(
                    'The user account for <strong>%s %s</strong> has been updated.',
                    $person->getFirstname(),
                    $person->getLastname()
                ));
            return new JsonModel(['status' => 'success','validation_errors' => null]);
        }

        return $viewModel;
    }

    /**
     * index action.
     *
     * @return ViewModel
     */
    public function indexAction()
    {

        $this->session = new Session('user_admin');
        $this->acl = $this->getEvent()->getApplication()
            ->getServiceManager()->get('acl');
        $get = $this->params()->fromQuery();
        if ($this->getRequest()->isXmlHttpRequest() && $get) {
            return $this->search();
        }
        $paginator = null;
        if ((! $get) && $this->session->params) {
            // not xhr, no query parameters, yes session parameters
            $page = isset($this->session->params['page']) ?
                $this->session->params['page'] : 1;
            $paginator = $this->entityManager
                ->getRepository(Entity\User::class)
                ->paginate(
                    $this->session->params['term'],
                    ['search_by' => $this->session->params['search_by'],
                    'page' => $this->params()->fromQuery(
                        'page',
                        $page
                    )]
                );
        }
        $repository = $this->entityManager
            ->getRepository('InterpretersOffice\Entity\Judge');
        $judges = $repository->getJudgeOptions();

        return new ViewModel(
            ['role' => $this->auth_user_role, 'judges' => $judges,
            'acl' => $this->acl,
            'defaults' => $this->session->params,'paginator' => $paginator ]
        );
    }

    /**
     * fetches users
     *
     * @return ViewModel
     */
    public function search()
    {
        $repository = $this->entityManager
                ->getRepository(Entity\User::class);
        $view = (new ViewModel())
            ->setTerminal(true)
            ->setTemplate('users/results');
        $get = $this->params()->fromQuery();
        /** @todo legit validation */
        if (empty($get['search_by']) or empty($get['term'])) {
            return $view->setVariables(
                ['errorMessage' => 'Sorry, invalid request parameters']
            );
        }
        $this->session->params = $get;
        /** @var Laminas\Paginator\Paginator $paginator */
        $paginator = $repository->paginate(
            $get['term'],
            ['search_by' => $get['search_by'],
            'page' => $this->params()->fromQuery(
                'page',
                1
            )]
        );

        return $view->setVariables(['paginator' => $paginator,'acl' => $this->acl]);
    }

    /**
     * view action for user/person details
     */
    public function viewAction()
    {
        $repo = $this->entityManager
            ->getRepository(Entity\User::class);
        $id = $this->params()->fromRoute('id');
        $entity = $this->params()->fromRoute('entity', 'user');
        $user = $repo->getUser($id, $entity);
        return ['entity' => $user,'id' => $id ];
    }

    /**
     * gets data for role element options
     *
     * this is for dynamically re-populating the Role element options based on
     * the currently selected Hat. route is /admin/users/role-options
     *
     * @return JsonModel
     */
    public function getRoleOptionsForHatAction()
    {
        $hat_id = $this->params()->fromRoute('hat_id');
        $repository = $this->entityManager
                ->getRepository('InterpretersOffice\Entity\Role');
        $data = $repository->getRoleOptionsForHatId(
            $hat_id,
            $this->auth_user_role
        );

        return new JsonModel($data);
    }

    /**
     * delete a user
     * @return JsonModel
     */
    public function deleteAction()
    {
        if (! $this->getRequest()->isPost()) {
            return new JsonModel(['status' => 'error','message' => 'POST method required']);
        }
        $id = $this->params()->fromRoute('id');
        $user = $this->entityManager->find(Entity\User::class, $id);
        $this->getEventManager()->trigger('load-user', $this, ['user' => $user,]);
        $person = $user->getPerson();
        $this->entityManager->remove($user);
        $this->entityManager->remove($person);
        try {
            $this->entityManager->flush();
            $this->flashMessenger()
                ->addSuccessMessage(sprintf(
                    'The user <strong>%s %s</strong> has been deleted.',
                    $person->getFirstname(),
                    $person->getLastname()
                ));
            return new JsonModel(['status' => 'success','message' => "This user has now been deleted."]);
        } catch (ForeignKeyConstraintViolationException $e) {
            return new JsonModel([
                'status' => 'error',
                'message' => 'Sorry, this user has a data history and can no longer be deleted.',
            ]);
        }
    }

    /**
     * autocomplete for admin/users
     *
     * @return JsonModel
     *
     */
    public function autocompleteAction()
    {
        /** @var InterpretersOffice\Entity\Repository\UserRepository $repository */
        $repository = $this->entityManager
                ->getRepository(Entity\User::class);
        $get = $this->params()->fromQuery();
        $data = $repository->autocomplete(
            $get['term'],
            ['search_by' => $get['search_by']]
        );

        return new JsonModel($data);
    }
}
