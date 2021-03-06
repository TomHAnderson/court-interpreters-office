<?php
/**  module/Requests/src/Form/ConfigFieldset.php */

namespace InterpretersOffice\Requests\Form;

use Laminas\Stdlib\ArrayObject;
use Laminas\Hydrator\ArraySerializable;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Form\Fieldset;

/**
 * fieldset for requests-configuration page
 */
class ConfigFieldset extends Fieldset implements InputFilterProviderInterface
{
    /**
     * constructor
     *
     * contructor options get passed on up to the parent except for
     * $options['config'], which supplies values we need to initialize
     * our checkbox element(s).
     *
     * @param string $name
     * @param array  $options
     */
    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);
        $config = $options['config'];
        
        foreach ($config as $name => $value) {
            $label = explode('.', $name)[2];
            
            $this->add(
                [
                    'type' => 'checkbox',
                    'name' => $name,
                    'attributes' => [
                            'class' => 'form-check-input',
                            'id'   => $name,

                    ],
                    'options' => [
                        'label' => str_replace('-', ' ', $label),
                        'use_hidden_element' => true,
                        'checked_value' => 1,
                        'unchecked_value' => 0,                        
                    ],
                ]
            );
            // shit will not accept "disabled" via the constructor, so we 
            // have to do this...
            if ($options && $options['disabled']){
                $this->get($name)->setAttribute('disabled','disabled');
            }
        }
    }

    /**
     * gets input filter specification
     *
     * doesn't do anything as of yet.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return [];
    }
}
