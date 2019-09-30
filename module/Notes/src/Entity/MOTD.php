<?php
/** MOTD.php */

declare(strict_types=1);

namespace InterpretersOffice\Admin\Notes\Entity;

use Doctrine\ORM\Mapping as ORM;
use InterpretersOffice\Entity\User;
use DateTime;

/**
 * Entity class representing MOTD
 *
 * @ORM\Entity
 * @ORM\Table(name="motd",uniqueConstraints={@ORM\UniqueConstraint(name="date_idx",columns={"date"})})
 * @ORM\HasLifecycleCallbacks
 */
class MOTD
{

    /**
     * entity id.
     *
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="smallint",options={"unsigned":true})
     */
    private $id;

    /**
     * date
     *
     * @ORM\Column(type="date",nullable=false)
     */
    private $date;

    /**
     * content
     *
     * @var @ORM\Column(type="string",nullable=false,length=2000)
     */
    private $content;

    /**
    * timestamp of motd creation.
    *
    * @ORM\Column(type="datetime",nullable=false)
    *
    * @var \DateTime
    */
    private $created;

    /**
     * last User who updated the motd.
     *
     * @ORM\ManyToOne(targetEntity="\InterpretersOffice\Entity\User")
     * @ORM\JoinColumn(nullable=false,name="created_by_id")
     */
    private $createdBy;


    /**
     * timestamp of last update.
     *
     * @ORM\Column(type="datetime",nullable=true)
     *
     * @var \DateTime
     */
    private $modified;


    /**
     * last User who updated the motd.
     *
     * @ORM\ManyToOne(targetEntity="\InterpretersOffice\Entity\User")
     * @ORM\JoinColumn(nullable=true,name="modified_by_id")
     */
    private $modifiedBy;

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
      * @return MOTD
      */
     public function setDate(DateTime $date)
     {
         $this->date = $date;

         return $this;
     }

     /**
      * Get date.
      *
      * @return \DateTime
      */
     public function getDate() : DateTime
     {
         return $this->date;
     }

     /**
      * Set content.
      *
      * @param string $content
      *
      * @return MOTD
      */
     public function setContent(string $content) : MOTD
     {
         $this->content = $content;

         return $this;
     }

     /**
      * Get content.
      *
      * @return string
      */
     public function getContent()  : string
     {
         return $this->content;
     }

     /**
      * Set created.
      *
      * @param \DateTime $created
      *
      * @return MOTD
      */
     public function setCreated(\DateTime $created)
     {
         $this->created = $created;

         return $this;
     }

     /**
      * Get created.
      *
      * @return \DateTime
      */
     public function getCreated() : DateTime
     {
         return $this->created;
     }

     /**
      * Set modified.
      *
      * @param \DateTime|null $modified
      *
      * @return MOTD
      */
     public function setModified(\DateTime $modified = null) : MOTD
     {
         $this->modified = $modified;

         return $this;
     }

     /**
      * Get modified.
      *
      * @return \DateTime|null
      */
     public function getModified() : ?DateTime
     {
         return $this->modified;
     }

     /**
      * Set createdBy.
      *
      * @param \InterpretersOffice\Entity\User $createdBy
      *
      * @return MOTD
      */
     public function setCreatedBy(\InterpretersOffice\Entity\User $createdBy)
     {
         $this->createdBy = $createdBy;

         return $this;
     }

     /**
      * Get createdBy.
      *
      * @return \InterpretersOffice\Entity\User
      */
     public function getCreatedBy() : User
     {
         return $this->createdBy;
     }

     /**
      * Set modifiedBy.
      *
      * @param User|null $modifiedBy
      *
      * @return MOTD
      */
     public function setModifiedBy(User $modifiedBy = null) :? User
     {
         $this->modifiedBy = $modifiedBy;

         return $this;
     }

     /**
      * Get modifiedBy.
      *
      * @return User|null
      */
     public function getModifiedBy() : ?User
     {
         return $this->modifiedBy;
     }
}