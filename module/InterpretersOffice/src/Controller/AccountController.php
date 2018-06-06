<?php
/**
 * module/InterpretersOffice/src/Controller/AccountController.php.
 */

namespace InterpretersOffice\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Doctrine\Common\Persistence\ObjectManager;
use InterpretersOffice\Entity;
use InterpretersOffice\Form\User\RegistrationForm;
use Zend\Authentication\AuthenticationServiceInterface;
use Zend\Form\FormInterface;

/**
 *  AccountController.
 *
 *  For registration, password reset and email verification.
 *  Very much incomplete.
 */

class AccountController extends AbstractActionController
{
    /**
     * objectManager instance.
     *
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * authentication service
     *
     * @var AuthenticationServiceInterface
     */
    protected $auth;
    /**
     * constructor.
     *
     * @param ObjectManager $objectManager
     * @param AuthenticationServiceInterface
     */
    public function __construct(ObjectManager $objectManager, AuthenticationServiceInterface $auth)
    {
        $this->objectManager = $objectManager;
        $this->auth = $auth;
    }

    /**
     * index action
     * @return ViewModel
     */
    public function indexAction()
    {
        return new ViewModel();
    }

    /**
     * partial validation
     *
     * @return JsonModel
     */
    public function validateAction()
    {
        $params = $this->params()->fromQuery();
        $form = new RegistrationForm($this->objectManager, [
            'action' => 'create','auth_user_role' => 'anonymous',
            ]);
        /* if (key_exists('interpreter', $params)) {
            $form->setValidationGroup(
                ['interpreter' => array_keys($params['interpreter'])]
            );
        */
        if (key_exists('user',$params)) {
            $input = $params['user'];
            if (key_exists('person', $input)) {
                $person_fields = true;
                $group = ['user'=>['person'=>array_keys($params['user']['person'])]];

            } else {
                $person_fields = false;
                $group = ['user'=>array_keys($params['user'])];
            }
            $form->setValidationGroup($group);
        }

        $form->setData($params);
        if (! $form->isValid()) {
            $messages = $form->getMessages()['user'];
            return new JsonModel(['validation_errors'=> $person_fields  ?
                $messages['person'] : $messages]);
        }
        return new JsonModel(['valid'=>true]);
    }

    /**
     * registers a new user account
     *
     * @return ViewModel
     */
    public function registerAction()
    {

        $form = new RegistrationForm($this->objectManager, [
            'action' => 'create','auth_user_role' => 'anonymous',
            ]);
        if (! $this->getRequest()->isPost()) {
            return new ViewModel(['form' => $form]);
        }
        // handle POST
        $user = new Entity\User();
        $request = $this->getRequest();
        $form->bind($user);
        $input = $request->getPost();
        $user = $input->get('user');

        $form->setData($input);
        if (! $form->isValid()) {
            printf('<pre>%s</pre>',print_r($form->getMessages(),true));
        }
        //printf('<pre>%s</pre>',print_r($form->getData(\Zend\Form\FormInterface::VALUES_AS_ARRAY),true));

        return new ViewModel(['form' => $form]);
    }

    /**
     * email verification
     * s
     * @return ViewModel
     */
    public function verifyEmailAction()
    {
        return new ViewModel();
    }

    /**
     * handles password-reset requests
     *
     * @return ViewModel
     */
    public function requestPasswordAction()
    {

        return new ViewModel();
    }

    /**
     * handles actual resetting of the user's password
     *
     * @return ViewModel
     */
    public function resetPasswordAction()
    {
        return new ViewModel();
    }
    /**
     * edit account profile
     *
     * @return ViewModel
     */
    public function editAction()
    {
        if (! $this->auth->hasIdentity()) {
            $this->redirect()->toRoute('auth');
            return;
        }
        return new ViewModel();
    }
}
