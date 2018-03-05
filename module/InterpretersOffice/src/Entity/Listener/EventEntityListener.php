<?php
/** module/InterpretersOffice/src/Entity/Listener/EventEntityListener.php */
namespace InterpretersOffice\Entity\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;

use Zend\Log\LoggerAwareInterface;

use InterpretersOffice\Entity;
use Zend\Log;
use Zend\Authentication\AuthenticationServiceInterface;
use Doctrine\ORM\EntityManager;

/**
 * Event entity listener.
 * Responsible for making sure certain meta data elements are set correctly.
 * For cache-related functions see {@see UpdateListener}
 */
class EventEntityListener implements EventManagerAwareInterface, LoggerAwareInterface
{
    use Log\LoggerAwareTrait;
    use EventManagerAwareTrait;

    /**
     * authentication service
     *
     * @var AuthenticationServiceInterface
     */
    protected $auth;


    /**
     * holds a copy of related entiti ids before update
     *
     * @var array
     */
    protected $state_before = [
        'interpreter_ids' => [],
        'defendant_ids'   => [],
    ];

    /**
     * constructor
     *
     * @param \DateTime
     */
    public function __construct()
    {
        $this->now = new \DateTime();
    }


    /**
     * sets authentication service
     *
     * @param AuthenticationServiceInterface $auth
     * @return EventEntityListener
     */
    public function setAuth(AuthenticationServiceInterface $auth)
    {
        $this->auth = $auth;
        return $this;
    }
    /**
     * postLoad callback
     *
     * @param Entity\Event $eventEntity
     * @param LifecycleEventArgs $event
     */
    public function postLoad(
        Entity\Event $eventEntity,
        LifecycleEventArgs $event
    ) {

        foreach ($eventEntity->getInterpreterEvents() as $interpEvent) {
            $this->state_before['interpreter_ids'][] =
                    $interpEvent->getInterpreter()->getId();
        }
        foreach ($eventEntity->getDefendantNames() as $defendant) {
            $this->state_before['defendant_ids'][] =
                    $defendant->getId();
        }
        $this->getEventManager()->trigger(__FUNCTION__, $this);
    }

    /**
     * preUpdate callback
     *
     * @param Entity\Event $eventEntity
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Entity\Event $eventEntity,
    PreUpdateEventArgs $args)
    {
        $modified = false;
        $debug = '';
        if ($args->getEntityChangeSet()) {
            $modified = true;
            // really?
            $debug .= "what changed? "
                    .print_r(array_keys($args->getEntityChangeSet()), true);
        }
        $interpreters_before = $this->state_before['interpreter_ids'];
        $interpreters_after = [];
        $interpreterEvents = $eventEntity->getInterpreterEvents();
        foreach ($interpreterEvents as $interpEvent) {
            $interpreters_after[] =
                    $interpEvent->getInterpreter()->getId();
        }
        if ($interpreters_before != $interpreters_after) {
             $modified = true;
             $added = array_diff($interpreters_after, $interpreters_before);
             // client-side-supplied created_by should agree with
             // currently authenticated user, but new entities can't get
             // inserted without a created_by id (we can't cascade=persist
             // for Interpreter with null id), so we have to set it in a hidden
             // form field, check it after the fact, and correct it if (in the
             // improbable case) it's necessary (until we come up with a better
             // plan).for Interpreter
             /** @todo factor out into its own function?  */
            $current_user_id = $this->auth->getStorage()->read()->id;
            foreach ($interpreterEvents as $ie) {
                $creator_id = $ie->getCreatedBy()->getId();
                if (in_array($ie->getInterpreter()->getId(), $added)) {
                    if ($creator_id != $current_user_id) {
                        $interpreter = $ie->getInterpreter();
                        $this->logger->warn(
                            sprintf(
                                'submitted creator id inconsistent with current user'
                                . ' in event id %d, interpreter id %d (%s)',
                                $eventEntity->getId(),
                                $added, $interpreter->getLastname()
                            ), compact('creator_id', 'current_user_id')
                        );
                    }
                    $ie->setCreatedBy(
                        $this->getAuthenticatedUser($args->getEntityManager())
                    );
                }
            }
        }

        $defendants_before = $this->state_before['defendant_ids'];
        $defendants_after = [];

        foreach ($eventEntity->getDefendantNames() as $deft) {
            $defendants_after[] = $deft->getId();
        }
        if ($defendants_after != $defendants_before) {
            $modified = true;
        }
        if ($modified) {
            $eventEntity
                    ->setModified($this->now)
                    ->setModifiedBy(
                        $this->getAuthenticatedUser($args->getEntityManager())
                    );
            $debug .= sprintf(
                " real changes detected, updating event meta for event id %s",
                $eventEntity->getId());
        } else {
            $debug .= "no actual update detected with event id "
                    .$eventEntity->getId();
        }
        $this->logger->info($debug);
    }


    /**
     * prePersist callback
     *
     * sets Event metadata, e.g., who created the Event and when
     *
     * @param \InterpretersOffice\Entity\Event $eventEntity
     * @param LifecycleEventArgs $event
     */
    public function prePersist(Entity\Event $eventEntity, LifecycleEventArgs $event)
    {

        if (! $eventEntity->getCreatedBy()) {
            // because in test environment, this might already have been done
            // for us
            $user = $this->getAuthenticatedUser($event->getEntityManager());
            $eventEntity->setCreatedBy($user);
        } else {
            // so we don't blow up in the test environment
            $user = $eventEntity->getCreatedBy();
        }

        $eventEntity->setCreated($this->now)
                ->setModifiedBy($user)
                ->setModified($this->now);
        foreach ($eventEntity->getInterpreterEvents() as $interpreterEvent) {
            $interpreterEvent
                    ->setCreatedBy($user)
                    ->setCreated($this->now);
        }
        $this->logger->debug(__FUNCTION__ . " in EventEntityListener really did shit");
    }

    /**
     * gets the User entity corresponding to authenticated identity
     *
     * @param EntityManager $em
     * @return Entity\User
     */
    protected function getAuthenticatedUser(EntityManager $em)
    {
        $dql = 'SELECT u FROM InterpretersOffice\Entity\User u WHERE u.id = :id';
        $id = $this->auth->getIdentity()->id;
        $query = $em->createQuery($dql)
                ->setParameters(['id' => $id])
                ->useResultCache(true);
        $user = $query->getOneOrNullResult();

        return $user;
    }
}
