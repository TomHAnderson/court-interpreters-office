<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity @ORM\Table(name="judges") 
 */

class Judge extends Person {
    
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="smallint",options={"unsigned":true})
     * @var int 
     */
    protected $id;
    
    /**
     * @ORM\Column(name="default_location_id")
     * @ORM\ManyToOne(targetEntity="Location") 
     */
    protected $defaultLocation;
    
    /**
     * @ORM\Column(type="string",length=10,nullable=false)
     * @var string USMJ|USDJ
     */
    protected $flavor;
    
    
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set defaultLocation
     *
     * @param Location $defaultLocation
     *
     * @return Judge
     */
    public function setDefaultLocation(Location $defaultLocation)
    {
        $this->defaultLocation = $defaultLocation;

        return $this;
    }

    /**
     * Get defaultLocation
     *
     * @return string
     */
    public function getDefaultLocation()
    {
        return $this->defaultLocation;
    }

    /**
     * Set flavor
     *
     * @param string $flavor
     *
     * @return Judge
     */
    public function setFlavor($flavor)
    {
        $this->flavor = $flavor;

        return $this;
    }

    /**
     * Get flavor
     *
     * @return string
     */
    public function getFlavor()
    {
        return $this->flavor;
    }

    
}
