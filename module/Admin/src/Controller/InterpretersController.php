<?php

/** module/Admin/src/Controller/PeopleController */

namespace InterpretersOffice\Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Doctrine\ORM\EntityManagerInterface;

use InterpretersOffice\Admin\Form\InterpreterForm;

use InterpretersOffice\Entity;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

/**
 * controller for admin/interpreters.
 */
class InterpretersController extends AbstractActionController
{
    /**
     * entity manager.
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * index action.
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        //echo "shit is working"; return false;
        return new ViewModel(['title' => 'interpreters']);
    }

    /**
     * adds a Person entity to the database.
     */
    public function addAction()
    {

        $viewModel = (new ViewModel())
                ->setTemplate('interpreters-office/admin/interpreters/form.phtml')
                ->setVariables(['title' => 'add an interpreter']);
        
        $form = new InterpreterForm($this->entityManager, ['action' => 'create']);
        $viewModel->form = $form;
        
        $request = $this->getRequest();
        $entity = new Entity\Interpreter();
        $form->bind($entity);
        if ($request->isPost()) {
            $form->setData($request->getPost());
            if (!$form->isValid()) {                      
                return $viewModel;
            }         
            $data = $request->getPost()['interpreter']['interpreter-languages'];
            $repository = $this->entityManager->getRepository('InterpretersOffice\Entity\Language');
            foreach($data as $language) {
                $id = $language['language_id'];
                $certification = is_numeric($language['federalCertification']) ?
                (boolean) $data['federalCertification'] : null;
                $languageEntity = $repository->find($id);
                $il = (new Entity\InterpreterLanguage($entity,$languageEntity))
                ->setFederalCertification($certification);
                $entity->addInterpreterLanguage($il);
            }
            
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            
            $this->flashMessenger()->addSuccessMessage(
                sprintf(
                    'The interpreter <strong>%s %s</strong> has been added to the database',
                    $entity->getFirstname(),
                    $entity->getLastname()
                )
            );
            //echo "success. NOT redirecting. <a href=\"/admin/interpreters/add\">again</a>";
            $this->redirect()->toRoute('interpreters');
        }
        return $viewModel;
    }
    
    

    
    /**
     * updates an Interpreter entity.
     */
    public function editAction()
    {
        
        $viewModel = (new ViewModel())
                ->setTemplate('interpreters-office/admin/interpreters/form.phtml')
                ->setVariable('title', 'edit an interpreter');
        $id = $this->params()->fromRoute('id');
        $entity = $this->entityManager->find('InterpretersOffice\Entity\Interpreter', $id);
        if (!$entity) {
            return $viewModel->setVariables(['errorMessage' => "interpreter with id $id not found"]);
        }
        
        $form = new InterpreterForm($this->entityManager, ['action' => 'update']);
        $form->bind($entity);
        $viewModel->setVariables(['form' => $form, 'id' => $id ]);
        $request = $this->getRequest();
        if ($request->isPost())
        {
            $form->setData($request->getPost());
            if (!$form->isValid()) {
                //echo "shit not valid?...";
                //echo "<pre>"; print_r($form->getMessages()); echo "</pre>"; 
                return $viewModel;
            }
            $this->updateInterpreterLanguages($entity,
                    $request->getPost()['interpreter']['interpreter-languages']);

            $this->entityManager->flush();
            $this->flashMessenger()->addSuccessMessage(sprintf(
                    'The interpreter <strong>%s %s</strong> has been updated.',
                    $entity->getFirstname(), $entity->getLastname()
            ));
            $this->redirect()->toRoute('interpreters');
           // echo "success. NOT redirecting...<a href=\"/admin/interpreters/edit/$id\">again</a> ";
           // echo "<pre>"; 
            //var_dump($_POST['interpreter']['interpreter-languages']) ;
                //print_r($form->getMessages());
           // echo "</pre>"; 
                
            ////entity:<pre>";
            //\Doctrine\Common\Util\Debug::dump($entity); echo "</pre>";
            
        } 
        return $viewModel;
    }
    
    /**
     * manually updates the Interpreter entity's languages
     * 
     * since we were unable to get the Doctrine hydrator to work, for reasons
     * that remain obscure, we do it ourself.
     * 
     * @param Entity\Interpreter $interpreter
     * @param array $languages language data POSTed to us
     * @return void
     */
    public function updateInterpreterLanguages(Entity\Interpreter $interpreter, Array $languages)
    {
        $repository = $this->entityManager->getRepository('InterpretersOffice\Entity\Language');
        // get $before and $after into two like-structured arrays for comparison
        $before = []; 
        $interpreterLanguages = $interpreter->getInterpreterLanguages();
        foreach ($interpreterLanguages as $il) {
            $array =  $il->toArray();
            $before[$array['language_id']] = $array['federalCertification'] ;
        }
        $after = [];
        foreach($languages as $l) {
            $after[$l['language_id']] = is_numeric($l['federalCertification'])? (boolean)$l['federalCertification'] : null;
            //echo "id {$l['language_id']} submitted as: {$after[$l['language_id']]}";
        }
        // what has been added?
        //echo "<pre>before: "; var_dump($before); 
        //echo "after: "; var_dump($after);
        //echo "</pre>";
        $added = array_diff_key($after,$before);
       
        //echo "<pre>diff (added):";var_dump($added); echo "</pre>"; 
        //return;
        if (count($added)) {
            
            foreach($added as $id => $cert) {
                $language = $repository->find($id);
                $obj = new Entity\InterpreterLanguage($interpreter,$language);
                //$cert = $after[$id];
                $obj->setFederalCertification($cert);
                //echo "Adding: ".(string)$language;
                $interpreter->addInterpreterLanguage($obj);
            }
        }
        // what has been removed?
        $removed = array_diff_key($before,$after);
        //echo "<pre>diff (removed):";var_dump($removed);echo "</pre>";
        
        if (count($removed)) {
            foreach($interpreterLanguages as $il) {
                $name = (string)$il->getLanguage();
                if (key_exists($il->getLanguage()->getId(),$removed)) {
                    $interpreter->removeInterpreterLanguage($il);
                   // echo "$name was removed...";
                }
            }            
        }
        //return;
        // was any certification was modified?
        $same = array_intersect_assoc($before,$after);
        //echo "<pre>same (languages):";print_r($same);echo "</pre>";
        //return;
        foreach($interpreter->getInterpreterLanguages() as $il) {
            
            $language = $il->getLanguage(); 
            $id = $il->getLanguage()->getId();
            $cert = $il->getFederalCertification();
            $submitted_cert = $after[$id];
            
            //echo "<pre>"; echo "id is $id; cert BEFORE: ";var_dump($cert);echo "as submitted: "; var_dump($after[$id]);
            if ($cert !== $submitted_cert) {
                //echo "...updating! ";
                $il->setFederalCertification($submitted_cert);
                //echo "... $language cert is(?) now: ";var_dump($il->getFederalCertification());
            }
            //echo "</pre>";
            
        }
    }
}

/*  // temporary garbage dump
 
 $after = [];
       $before = $this->interpreterLanguages;
       foreach ($data['interpreter-languages'] as $index => $language_data) {  
           $after[$language_data['language_id']] = [
               'federalCertification' => $language_data['federalCertification'],
           ];
           // just stick them all in there, it blows up with duplicate entry

           $language = $repository->find($language_data['language_id']);
           $interpreterLanguage = new Entity\InterpreterLanguage($entity,$language);
            if (null === $language_data['federalCertification']) {
                $federalCertification = null;
            } else {
                $federalCertification = $language_data['federalCertification'] == 1 ? true : false;
            }            
           
           $interpreterLanguage->setFederalCertification($federalCertification);
           $entity->addInterpreterLanguage(new Entity\InterpreterLanguage($entity,$language));                        
           
       }
       $modified = $before != $after;
       echo "<pre>before: "; print_r($before); echo "after: "; print_r($after); echo "</pre>";
       
       if ($modified) {
           echo "yes, modified...";
           $to_be_removed = array_diff_key($before,$after);
           $to_be_added   = array_diff_key($after,$before);
           printf("%d to remove, %d to add<br>",count($to_be_removed),count($to_be_added));
           // to be continued: figure out how to handle updated federalCertification
       } else {
           echo "NOT modified? ";
           //$entity->removeInterpreterLanguages($entity->getInterpreterLanguages());
       } 
 
 */
/*
 * 
     * manually deals with hydration of the Interpreter's languages
     * 
     * @param \InterpretersOffice\Entity\Interpreter $entity
     * @param array $data
     * @param DoctrineHydrator $hydrator
     *
    protected function hydrate(Entity\Interpreter $entity,Array $data, DoctrineHydrator $hydrator)
    {
        
        //echo "DATA:<pre>"; print_r($data['interpreter-languages']); echo "</pre>";
       $repository = $this->entityManager->getRepository('InterpretersOffice\Entity\Language');
       
       $action = $this->params()->fromRoute('action');
       if ('edit' == $action) {
           echo "<br>this is an update involving {$entity->getId()}...";
       }
       $entity->removeInterpreterLanguages(
            $entity->getInterpreterLanguages()
        );
       foreach ($data as $language_data) {
             $language = $repository->find($language_data['language_id']);
             $il = new Entity\InterpreterLanguage($entity,$language);
             //$this->entityManager->persist($il);
             $entity->addInterpreterLanguage($il);
       }
       return;

       $interpreterLanguages = [];
       if (true)
        {
           foreach ($data as $language_data) {

                if (null === $language_data['federalCertification']) {
                    $federalCertification = null;
                } else {
                    $federalCertification = $language_data['federalCertification'] == 1 ? true : false;
                }
                $language = $repository->find($language_data['language_id']);

                printf("THE FUCKING LANGUAGE ID IS %s",$language->getId()   ); 
                $interpreterLanguages[] = 
                    [
                        'language' => $language, // ['id'=>$language_data['language_id']],
                        'interpreter' => $entity,
                        'federalCertification' => $federalCertification,
                    ];
           }
           $data = ['interpreterLanguages' => $interpreterLanguages,];
           echo "<pre>shit: ";
           \Doctrine\Common\Util\Debug::dump($data['interpreterLanguages']);
           echo "</pre>";
           $hydrator->hydrate($data, $entity);
       }
    }
 */