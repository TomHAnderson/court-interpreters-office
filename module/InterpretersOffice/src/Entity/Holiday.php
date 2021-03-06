<?php

/** module/InterpretersOffice/src/Entity/Holiday.php */

namespace InterpretersOffice\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entity representing an official court holiday.
 *
 *
 * @ORM\Entity  @ORM\Table(name="holidays")
 *
 * @see InterpretersOffice\Entity\CourtClosing
 */
class Holiday
{
    /**
     * entity id.
     *
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * name of the holiday.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

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
     * Set name.
     *
     * @param string $name
     *
     * @return Holiday
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
