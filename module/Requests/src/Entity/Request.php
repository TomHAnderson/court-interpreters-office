<?php /** module/Requests/src/Entity/Request.php */

namespace InterpretersOffice\Requests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use InterpretersOffice\Entity\Person;
use InterpretersOffice\Entity\Interpreter;
use InterpretersOffice\Entity\Interpretable;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Request -- entity representing a request for interpreting services
 *
 * @ORM\Entity(repositoryClass="InterpretersOffice\Requests\Entity\RequestRepository");
 * @ORM\Table(name="requests")
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({"InterpretersOffice\Requests\Entity\Listener\RequestEntityListener"})
 */
class Request implements Interpretable, ResourceInterface
{

    /**
     * entity id.
     *
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="smallint",options={"unsigned":true})
     */
    protected $id;

    /**
     * date on which the event takes place.
     *
     * @ORM\Column(type="date",nullable=false)
     */
    protected $date;

    /**
     * time at which the event takes place.
     *
     * The date and time are stored in separate columns because there are cases
     * where the date is known but the time is unknown or to-be-determined.
     *
     * @ORM\Column(type="time",nullable=false)
     */
    protected $time;

    /**
     * language.
     *
     * @ORM\ManyToOne(targetEntity="\InterpretersOffice\Entity\Language") //,inversedBy="events"
     * @ORM\JoinColumn(nullable=false)
     *
     * @var Language
     */
    protected $language;

    /**
     * Every event is of some type or other.
     *
     * @ORM\ManyToOne(targetEntity="\InterpretersOffice\Entity\EventType",inversedBy="events")
     * @ORM\JoinColumn(nullable=false,name="event_type_id")
     *
     * @var EventType
     */
    protected $event_type;

    /**
     * Judge.
     *
     * @ORM\ManyToOne(targetEntity="\InterpretersOffice\Entity\Judge") //,inversedBy="events"
     * @ORM\JoinColumn(nullable=true)
     *
     * @var Judge
     */
    protected $judge;

    /**
     * Anonymous or generic judge.
     *
     * While most events have a Judge, in a few cases the identity of
     * the judge/person is unknown, irrelevant or not applicable.
     *
     * @ORM\ManyToOne(targetEntity="\InterpretersOffice\Entity\AnonymousJudge")
     * @ORM\JoinColumn(nullable=true,name="anonymous_judge_id")
     *
     * @var AnonymousJudge
     */
    protected $anonymous_judge;

    /**
     * Person who creates (hence submits) this request.
     *
     * The interpreter is requested by a Person (submitter). For requests
     * submitted through this application (rather than phone, email, etc),
     * the submitter_id is identical with the current user/person who creates
     * the Request entity. One might think this should point to a User, but the
     * Event entity's submitter property points to a Person -- which is because
     * the submitter might not be someone with a user account -- so it makes
     * some sense to do the same here.
     *
     * @ORM\ManyToOne(targetEntity="\InterpretersOffice\Entity\Person")
     * @ORM\JoinColumn(nullable=true)
     *
     * @var Entity\Person
     */
    protected $submitter;

    /**
     * the docket number.
     *
     * @ORM\Column(type="string",length=15,nullable=false,options={"default":""})
     *
     * @var string
     */
    protected $docket = '';


    /**
     * location.
     *
     * @ORM\ManyToOne(targetEntity="\InterpretersOffice\Entity\Location")
     * @ORM\JoinColumn(nullable=true)
     *
     * @var Entity\Location
     */
    protected $location;

    /**
     * date/time when Request was created.
     *
     * @ORM\Column(type="datetime",nullable=false)
     *
     * @var \DateTime
     */
    protected $created;

    /**
     * timestamp of last update.
     *
     * @ORM\Column(type="datetime",nullable=true)
     *
     * @var \DateTime
     */
    protected $modified;

    /**
     * last User who updated the Request.
     *
     * @ORM\ManyToOne(targetEntity="\InterpretersOffice\Entity\User")
     * @ORM\JoinColumn(nullable=true,name="modified_by_id")
     */
    protected $modified_by;

    /**
     * comments.
     *
     * @ORM\Column(type="string",length=1000,nullable=false,options={"default":""})
     *
     * @var string
     */
    protected $comments = '';

    /**
     * scheduled event
     *
     * @ORM\OneToOne(targetEntity="\InterpretersOffice\Entity\Event")
     * @var Entity\Event
     */
    protected $event;

    /**
     * If the event property is null, it means that the event is either pending
     * or else was once scheduled and later deleted. This flag enables us to
     * determine which is the case.
     *
     * @ORM\Column(type="boolean",options={"nullable":false,"default":true})
     *
     * @var bool true if request is "pending"
     */
    protected $pending = true;


    /**
     * cancelled
     *
     * true if a user cancels the Request
     * @ORM\Column(type="boolean",options={"nullable":false,"default":false})
     * @var boolean
     */
    protected $cancelled = false;

    /**
     * Defendant names.
     *
     * @ORM\ManyToMany(targetEntity="\InterpretersOffice\Entity\Defendant")
     * @ORM\JoinTable(name="defendants_requests",
     *      joinColumns={@ORM\JoinColumn(name="request_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="defendant_id", referencedColumnName="id")}
     * )
     *
     * @var ArrayCollection
     */
    protected $defendants;


    /**
     * Extra data in JSON format
     *
     * This is for stuffing extra stuff into the record that doesn't belong
     * anywhere else, e.g., defendant names that they could not or would not
     * locate in the database but which we do not want them to try to insert
     *
     * @ORM\Column(type="string",name="extra_json_data",length=500,nullable=false,options={"default":""})
     *
     * @var string
     */
    protected $extraData = '';


     /**
     * Constructor
     */
    public function __construct()
    {
        $this->defendants = new ArrayCollection();
    }


    /**
     * ensures that creation datetime is set
     *
     * @ORM\PrePersist
     *
     */
    public function prePersist()
    {
        $now = null;
        if (! $this->created) {
            $now = new \DateTime();
            $this->setCreated($now);
        }
        if (! $this->modified) {
            $this->setModified($now ?: new \DateTime());
        }
        if (! $this->getSubmitter()) {
            throw new \RuntimeException(
                'Request submitter property can\'t be null'
            );
        }
    }
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return Request
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set time.
     *
     * @param \DateTime $time
     *
     * @return Request
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time.
     *
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set docket.
     *
     * @param string $docket
     *
     * @return Request
     */
    public function setDocket($docket)
    {
        $this->docket = $docket;

        return $this;
    }

    /**
     * Get docket.
     *
     * @return string
     */
    public function getDocket()
    {
        return $this->docket;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return Request
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set modified.
     *
     * @param \DateTime|null $modified
     *
     * @return Request
     */
    public function setModified($modified = null)
    {
        $this->modified = $modified;

        return $this;
    }

    /**
     * Get modified.
     *
     * @return \DateTime|null
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set comments.
     *
     * @param string $comments
     *
     * @return Request
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments.
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set pending.
     *
     * @param bool $pending
     *
     * @return Request
     */
    public function setPending($pending)
    {
        $this->pending = $pending;

        return $this;
    }

    /**
     * Get pending.
     *
     * @return bool
     */
    public function getPending()
    {
        return $this->pending;
    }

    /**
     * Set language.
     *
     * @param \InterpretersOffice\Entity\Language $language
     *
     * @return Request
     */
    public function setLanguage(\InterpretersOffice\Entity\Language $language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language.
     *
     * @return \InterpretersOffice\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set event_type.
     *
     * @param \InterpretersOffice\Entity\EventType $event_type
     *
     * @return Request
     */
    public function setEventType(\InterpretersOffice\Entity\EventType $event_type)
    {
        $this->event_type = $event_type;

        return $this;
    }

    /**
     * Get event_type.
     *
     * @return \InterpretersOffice\Entity\EventType
     */
    public function getEventType()
    {
        return $this->event_type;
    }

    /**
     * Set judge.
     *
     * @param \InterpretersOffice\Entity\Judge|null $judge
     *
     * @return Request
     */
    public function setJudge(\InterpretersOffice\Entity\Judge $judge = null)
    {
        $this->judge = $judge;

        return $this;
    }

    /**
     * Get judge.
     *
     * @return \InterpretersOffice\Entity\Judge|null
     */
    public function getJudge()
    {
        return $this->judge;
    }

    /**
     * Set anonymous_judge.
     *
     * @param \InterpretersOffice\Entity\AnonymousJudge|null $anonymous_judge
     *
     * @return Request
     */
    public function setAnonymousJudge(\InterpretersOffice\Entity\AnonymousJudge $anonymous_judge = null)
    {
        $this->anonymous_judge = $anonymous_judge;

        return $this;
    }

    /**
     * Get anonymous_judge.
     *
     * @return \InterpretersOffice\Entity\AnonymousJudge|null
     */
    public function getAnonymousJudge()
    {
        return $this->anonymous_judge;
    }

    /**
     * Set submitter.
     *
     * @param \InterpretersOffice\Entity\Person|null $submitter
     *
     * @return Request
     */
    public function setSubmitter(\InterpretersOffice\Entity\Person $submitter = null)
    {
        $this->submitter = $submitter;

        return $this;
    }

    /**
     * Get submitter.
     *
     * @return \InterpretersOffice\Entity\Person|null
     */
    public function getSubmitter()
    {
        return $this->submitter;
    }

    /**
     * Set location.
     *
     * @param \InterpretersOffice\Entity\Location|null $location
     *
     * @return Request
     */
    public function setLocation(\InterpretersOffice\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return \InterpretersOffice\Entity\Location|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set modified_by.
     *
     * @param \InterpretersOffice\Entity\User|null $modified_by
     *
     * @return Request
     */
    public function setModifiedBy(\InterpretersOffice\Entity\User $modified_by = null)
    {
        $this->modified_by = $modified_by;

        return $this;
    }

    /**
     * Get modified_by.
     *
     * @return \InterpretersOffice\Entity\User|null
     */
    public function getModifiedBy()
    {
        return $this->modified_by;
    }

    /**
     * Set event.
     *
     * @param \InterpretersOffice\Entity\Event|null $event
     *
     * @return Request
     */
    public function setEvent(\InterpretersOffice\Entity\Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event.
     *
     * @return \InterpretersOffice\Entity\Event|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Add defendant.
     *
     * @param \InterpretersOffice\Entity\Defendant $defendant
     *
     * @return Request
     */
    public function addDefendant(\InterpretersOffice\Entity\Defendant $defendant)
    {
        $this->defendants[] = $defendant;

        return $this;
    }

    /**
     * Remove defendant.
     *
     * @param \InterpretersOffice\Entity\Defendant $defendant
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeDefendant(\InterpretersOffice\Entity\Defendant $defendant)
    {
        return $this->defendants->removeElement($defendant);
    }

    /**
     * Get defendants.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDefendants()
    {
        return $this->defendants;
    }

    /**
     * adds Defendants.
     *
     * @param Collection $defendants
     */
    public function addDefendants(Collection $defendants)
    {
        foreach ($defendants as $defendant) {
            $this->defendants->add($defendant);
        }
    }

    /**
     * removes defendants.
     *
     * @param Collection $defendants
     */
    public function removeDefendants(ArrayCollection $defendants)
    {
        foreach ($defendants as $defendant) {
            $this->defendants->removeElement($defendant);
        }
    }

    /**
     * gets the cancelled property
     * @return boolean
     */
    public function getCancelled()
    {
        return $this->cancelled;
    }

    /**
     * sets $cancelled
     *
     * @param Request
     */
    public function setCancelled($flag)
    {
        $this->cancelled = $flag;

        return $this;
    }

    /**
     * proxies to getCancelled
     *
     * @return boolean
     */
    public function isCancelled()
    {
        return $this->getCancelled();
    }
    /**
     * sets "extra" data
     *
     * @param Array $data [description]
     * @return Request
     */
    public function setExtraData(Array $data)
    {
        $json = json_encode($data);
        $this->extraData = $json;

        return $this;
    }

    /**
     * gets extra data
     *
     * @return Array|null
     */
    public function getExtraData()
    {
        return $this->extraData ?
            json_decode($this->extraData, \JSON_OBJECT_AS_ARRAY) : null;
    }

    /**
     * gets extra defendant names
     *
     * @return Array|null
     */
    public function getExtraDefendantNames()
    {
        $data = $this->getExtraData();

        return isset($data['defendants']) ? $data['defendants'] : [];
    }

    /**
     * gets Interpreters
     *
     * @return Interpreter[]
     */
    public function getInterpreters() : Array
    {
        $e = $this->getEvent();
        if (! $e) {
            return [];
        }
        return $e->getInterpreters();
    }
}
