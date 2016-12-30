<?php
/**
 * module/InterpretersOffice/Form/CsrfElementCreationTrait.php
 *  
 */
namespace InterpretersOffice\Form;

use Zend\Form\Element\Csrf;
use Zend\InputFilter;

/**
 * trait to facilitate adding a CSRF element to a Form.
 * 
 * yes, we could create subclasses of Zend\Form\Form and Zend\Form\Fieldset that
 * do this, and extend those instead. but this works, and Traits are cool. 
 * 
 */
trait CsrfElementCreationTrait
{

    /**
     * adds a CSRF element to the form
     * 
     * @return mixed. whatever $this is (Form or Fieldset instance).
     */
	function addCsrfElement()
	{

		$this->add(new Csrf('csrf'));
        // customize validation error messages
        $inputFilter = $this->getInputFilter();
        $factory = new InputFilter\Factory();
        $inputFilter->merge(
            $factory->createInputFilter([
                'csrf' => [
                    'name' => 'csrf',
                    'validators' => [
                        [
                            'name' => 'Zend\Validator\NotEmpty',
                            'options' => [
                                'messages' => [
                                    'isEmpty' => 'security error: missing CSRF token',
                                ],
                            ],
                        ],
                        [
                            'name' => 'Zend\Validator\Csrf',
                            'options' => [
                                'messages' => [
                                    'notSame' => 'security error: invalid CSRF token',
                                ],
                            ],
                        ],
                    ],
                ]
            ])
        );
      	return $this;
	}
}