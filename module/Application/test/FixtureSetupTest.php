<?php
namespace ApplicationTest;


use Zend\Stdlib\ArrayUtils;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
//use PHPUnit_Framework_TestCase;

use Application\Entity;

/** @todo put this bit in a bootstrap file and add it to phpunit.xml ? */

//require __DIR__. '/../../../vendor/autoload.php';
    
class FixtureSetupTest extends  
   // PHPUnit_Framework_TestCase
   AbstractHttpControllerTestCase
{
    
    public function setUp()
    {
        ///*
        $configOverrides = [
            
            'module_listener_options' => [
                'config_glob_paths' => [
                    __DIR__.'/config/autoload/{{,*.}global,{,*.}local}.php'
                ],
            ],
            
        ];
        
        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../config/application.config.php',
            $configOverrides
        ));
       // */
        parent::setUp();
    }
    /**
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->getApplicationServiceLocator()->get('entity-manager');
    }
    public function testSomething()
    {       
        
        $container = $this->getApplicationServiceLocator();
        $this->assertTrue($container instanceof \Interop\Container\ContainerInterface);
        
        $objectManager = $this->getEntityManager();
        $connection = $objectManager->getConnection();
        $driver = $connection->getDriver();
        $this->assertEquals('pdo_sqlite',$driver->getName());
        $this->dispatch('/');
        $this->assertResponseStatusCode(200);
       
    }
    public function loadTestEventData()
    {
        $fixtureExecutor = FixtureManager::getFixtureExecutor();
        //$entityManager->getC
        //FixtureManager::start();
        $fixtureExecutor->execute([
            new DataFixture\LanguageLoader(),
            new DataFixture\HatLoader(),
            new DataFixture\EventTypeLoader(),
            new DataFixture\LocationLoader(),
            new DataFixture\DefendantNameLoader(),
            new DataFixture\JudgeLoader(),
            new DataFixture\InterpreterLoader(),
            new DataFixture\CancellationReasonLoader(),
            new DataFixture\UserLoader(),
            new DataFixture\EventLoader(),
         ]);
    }
    public function testDataFixtureSanity() {
        
        $this->assertTrue(class_exists('ApplicationTest\FixtureManager'));
        $fixtureExecutor = FixtureManager::getFixtureExecutor();
        $this->assertTrue(is_object($fixtureExecutor));
        $entityManager = FixtureManager::getEntityManager();
        //$entityManager->getC
        //FixtureManager::start();
        $fixtureExecutor->execute([
            new DataFixture\LanguageLoader(),
            new DataFixture\HatLoader(),
            new DataFixture\EventTypeLoader(),
            new DataFixture\LocationLoader(),
            new DataFixture\DefendantNameLoader(),
            new DataFixture\JudgeLoader(),
            new DataFixture\InterpreterLoader(),
            new DataFixture\CancellationReasonLoader(),
            new DataFixture\UserLoader(),
            new DataFixture\EventLoader(),
         ]);
        
        $this->assertTrue(is_object($entityManager));
        //echo get_class($entityManager);
        $languages = $entityManager->getRepository('Application\Entity\Language')->findAll();
        $this->assertTrue(is_array($languages));
        /** @var Doctrine\DBAL\Connection $connection */
        $connection = $entityManager->getConnection();
        $count = (int) $connection->fetchColumn("select count(*) from languages");
        $this->assertEquals($count,count($languages));

    }
    /**
     * test that a RuntimeException will be thrown if we try to persist an Event
     * with no Judge and no anonymous judge.
     */
    public function testExceptionThrownWhenNoJudgeOrAnonymousJudgeIsSet() {
        
        $this->loadTestEventData();
        $event = new Entity\Event();
                $date = new \DateTime("next monday");

        $time = new \DateTime('10:00 am');
        $objectManager = FixtureManager::getEntityManager();
        $language = $objectManager->getRepository('Application\Entity\Language')
                ->findOneBy(['name'=>'Spanish']);

        $eventType = $objectManager->getRepository('Application\Entity\EventType')
                ->findOneBy(['name'=>'pretrial conference']);

        $comments = 'test one two';

        $dql = "SELECT u FROM Application\Entity\User u JOIN u.person p "
                . "WHERE p.email = 'john_somebody@nysd.uscourts.gov'";
        $query = $objectManager->createQuery($dql);
        $user = $query->getSingleResult();

        $interpreter = $objectManager->getRepository('Application\Entity\Interpreter')
                ->findOneBy(['lastname'=>'Mintz']);
        
        $defendant =  $objectManager->getRepository('Application\Entity\DefendantName')
                ->findOneBy(['surnames'=>'Fulano Mengano']);
        $event = new Entity\Event();
        $now = new \DateTime(); 
        //$judge = $objectManager->getRepository('Application\Entity\Judge')
        //        ->findOneBy(['lastname'=>'Failla']);
        $event
            ->setDate($date)
            ->setTime($time)
            ->setJudge(null)
            ->setLanguage($language)
            ->setEventType($eventType)
            ->setDocket('2016-CR-0123')
            ->setComments($comments)
            ->setAdminComments('')
            ->setSubmitter($user->getPerson())
            ->setModified($now)
            ->setCreated($now)
            ->setCreatedBy($user)
            ->setModifiedBy($user)
            ->addInterpretersAssigned(
                 (new Entity\InterpreterEvent($interpreter,$event))->setCreatedBy($user)
             )
             ->addDefendant($defendant);//->setJudge($judge); 
        
        $this->expectException(\RuntimeException::class);
        // this should suffice to throw a RuntimeException
        // and prove our lifecycle callback works
        $objectManager->persist($event);
        
    }
}


