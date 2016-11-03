<?php

/** module/Application/src/Application/Entity/Language.php */

namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation;

/** 
 * Entity class representing a language used by an Interpreter.
 * 
 * @Annotation\Name("language")
 * @ORM\Entity  
 * @ORM\Table(name="languages",uniqueConstraints={@ORM\UniqueConstraint(name="unique_language",columns={"name"})}) 
 */

class Language
{
    /**
     * entity id
     * 
     * @Annotation\Attributes({"type":"hidden"})
     * @ORM\Id 
     * @ORM\GeneratedValue @ORM\Column(type="smallint",options={"unsigned":true})
     */
    protected $id;

    /**
     * name of the language.
     * 
     * @Annotation\Attributes({"type":"text","placeholder":"the name of the language","size":36,"class":"form-control"})
     * @Annotation\Options({"label":"name"})
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Filter({"name":"StripTags"})
     * @Annotation\Validator({"name":"NotEmpty",
     *  "break_chain_on_failure": true,
     *  "options":{"messages":{"isEmpty":"language name is required"}
     *  }})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":2, "max":50,
     *  "messages":{"stringLengthTooShort":"language name must be at least 2 characters long",
     *   "stringLengthTooLong":"language name exceeds maximum length of 50 characters"}}})
     * @ORM\Column(type="string",length=50,nullable=false)
     *
     * @var string
     */
    protected $name;

    /**
     * comments.
     * 
     * @Annotation\Attributes({"type":"textarea", "cols":36,"rows":4,"class":"form-control","placeholder":"optionally, a few notes about this language"})
     * @Annotation\Options({"label":"notes"})
     * @Annotation\AllowEmpty()
     * @Annotation\Validator({"name":"StringLength", "options":{"max":200,
     * "messages":{ "stringLengthTooLong":
     *      "comments exceed the maximum length of 200 characters"}
     * }})
     * @ORM\Column(type="string",length=200,nullable=false,options={"default":""})
     *
     * @var string
     */
    protected $comments = '';

    
    /**
     * Is there a federal certification program for this language?
     *
     * There are only three such languages and that is very unlikely to change.
     * So unlikely that we can hard-code them.
     *
     * @return bool
     */
    public function is_federally_certified()
    {
        return in_array($this->name, [
            'Spanish',
            'Navajo',
            'Haitian Creole',
        ]);
    }

    /**
     * returns a string representation 
     *  
     * @return string
     * 
     */
    public function __toString()
    {
        return $this->name;
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
     * Set name.
     *
     * @param string $name
     *
     * @return Language
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

    /**
     * Set comments.
     *
     * @param string $comments
     *
     * @return Language
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
    

    
}
