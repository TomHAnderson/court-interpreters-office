<?php
/** module/Rotation/test/RotationEntityTest.php */

use InterpretersOffice\Admin\Rotation\Entity;
use InterpretersOffice\Entity\Person;
use PHPUnit\Framework\TestCase;

/**
 * depends on our mysql dev database, ergo doesn't work without out it
 */
class RotationEntityTest extends TestCase
{

    /**
     * entity manager
     *
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    public function setUp()
    {

        $task_name = 'Some Shit';
        $this->em = require __DIR__.'/../../../config/doctrine-bootstrap.php';
        $task = $this->em->createQuery('SELECT t FROM ' .Entity\Task::class. ' t WHERE t.name = :name')
        ->setParameters(['name'=>$task_name])->getOneOrNullResult();
        if ($task) {
            $rotations = $this->em->createQuery('SELECT r FROM '.Entity\Rotation::class. ' r
             JOIN r.task t WHERE t.name = :name')->setParameters(['name'=>$task_name])
             ->getResult();
             if ($rotations) {
                 foreach($rotations as $r) {
                    $this->em->remove($r);
                 }
             }
             $this->em->remove($task);
             $this->em->flush();
        }
    }


    public function testCreateTask()
    {
        $repo = $this->em->getRepository(Entity\Rotation::class);
        $count = $repo->count([]);
        $task = new Entity\Task();
        $task->setName('Some Shit')
            ->setDescription('bla bla')
            ->setDuration('WEEK')->setFrequency('WEEK');
        $rotation = new Entity\Rotation();
        $rotation->setStartDate(new \DateTime('2015-05-18'))->setTask($task);

        $person_repo = $this->em->getRepository(Person::class);
        foreach ([881,840,862,199,198] as $order => $id) {
            $member = new Entity\RotationMember();
            $person = $person_repo->find($id);
            $member->setPerson($person)->setRotation($rotation)->setOrder($order);
            $rotation->addMember($member);
            $this->em->persist($member);
        }
        $task->addRotation($rotation);
        $em = $this->em;
        $em->persist($rotation);
        $em->persist($task);
        $em->flush();

        $this->assertEquals(++$count, $repo->count([]));

        //return $task;

    }
    /**
     *
     *
     */
    public function testGetDefaultSchedulingVictim()
    {
        $task = $this->em->createQuery('SELECT t FROM '.Entity\Task::class. ' t WHERE t.name LIKE :name')
            ->setParameters(['name'=>'%scheduling%'])->getOneOrNullResult();
        $this->assertTrue($task instanceof Entity\Task);
        $repo = $this->em->getRepository(Entity\Rotation::class);
        $example_date = new \DateTime('2019-11-06');
        $assigned = $repo->getDefaultAssignedPerson($task,$example_date);
        $this->assertTrue(is_object($assigned),"expected \$assigned to be object, got ".gettype($assigned));
        $this->assertTrue($assigned instanceof Person, "expected instance of Person, got ".get_class($assigned));
        $this->assertEquals("Paula",$assigned->getFirstName(),'expected "Paula", got '.$assigned->getFirstName());
        //printf("\ndate %s, assigned: %s\n",$example_date->format('D d-M-Y'),$assigned->getFirstName());
        // Mirta is default, Humberto is sub
        $example_date = new DateTime('2019-10-25');
        $default = $repo->getDefaultAssignedPerson($task,$example_date);
        //printf("\ndate %s; default: %s\n",$example_date->format('D d-M-Y'),$default->getFirstName());
        $example_date = new DateTime('2019-10-25');
        $actual = $repo->getAssignedPerson($task,$example_date);
        // printf("\ndate %s; actually assigned: %s\n",$example_date->format('D d-M-Y'),$actual['assigned']->getFirstName());
    }

    public function testGetActualSchedulingVictimWhenSubstitutionOccurs()
    {
        $task = $this->em->createQuery('SELECT t FROM '.Entity\Task::class. ' t WHERE t.name LIKE :name')
            ->setParameters(['name'=>'%scheduling%'])->getOneOrNullResult();

        $repo = $this->em->getRepository(Entity\Rotation::class);
        $example_date = new DateTime('2019-10-25');
        $actual = $repo->getAssignedPerson($task,$example_date);
        $this->assertEquals("Humberto",$actual['assigned']->getFirstName());
        $this->assertEquals("Mirta",$actual['default']->getFirstName());
    }

    public function testGetDefaultSaturdayVictim()
    {
        $task = $this->em->createQuery('SELECT t FROM '.Entity\Task::class. ' t WHERE t.name LIKE :name')
            ->setParameters(['name'=>'%saturday%'])->getOneOrNullResult();
        $this->assertTrue($task instanceof Entity\Task);
        $repo = $this->em->getRepository(Entity\Rotation::class);
        $example_date = new \DateTime('2019-11-06');
        $default = $repo->getDefaultAssignedPerson($task,$example_date);
        $this->assertEquals("Erika",$default->getFirstName());

    }

    public function testGetActualSaturdayVictimWhenSubstitutionOccurs()
    {
        $task = $this->em->createQuery('SELECT t FROM '.Entity\Task::class. ' t WHERE t.name LIKE :name')
            ->setParameters(['name'=>'%saturday%'])->getOneOrNullResult();

        $repo = $this->em->getRepository(Entity\Rotation::class);
        $example_date = new DateTime('2019-11-02');
        $actual = $repo->getAssignedPerson($task,$example_date);
        //printf("\ndate %s; actually assigned: %s\n",$example_date->format('D d-M-Y'),$actual['assigned']->getFirstName());
        $this->assertEquals("Mirta",$actual['default']->getFirstName());
        $this->assertEquals("Humberto",$actual['assigned']->getFirstName());

        $example_date = new DateTime('2019-10-30');
        $actual = $repo->getAssignedPerson($task,$example_date);
        //printf("\ndate %s; actually assigned: %s\n",$example_date->format('D d-M-Y'),$actual['assigned']->getFirstName());
        $this->assertEquals("Mirta",$actual['default']->getFirstName());
        $this->assertEquals("Humberto",$actual['assigned']->getFirstName());

    }
}