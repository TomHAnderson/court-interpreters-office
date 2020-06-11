<?php
/**  module/Requests/src/Form/ConfigForm.php */

namespace InterpretersOffice\Requests\Form;

use Laminas\Stdlib\ArrayObject;
use Laminas\Hydrator\ArraySerializable;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Form\Form;

/**
 * configure Requests module -- work in progress
 */
class ConfigForm extends Form implements InputFilterProviderInterface
{
    
    /**
     * whether form controls should be read-only
     */
    private $disabled;

    /**
     * constructor
     */
    public function __construct(Array $options)
    {
        parent::__construct('config-form');
        $this->disabled = $options['disabled'];

        $this->init();
    }

    /**
     * config data for Request module event listeners
     *
     * @var array
     */
    public $data;

    /**
     * initialization
     *
     * If they set a custom configuration, a custom.event-listeners.json is
     * created. If they restore the defaults, it is deleted. Therefore, the
     * existence of a custom.event-listeners.json means they are using it, hence
     * it's the one to load.
     *
     * @return void
     */
    public function init()
    {
        $basedir = 'module/Requests/config';
        $prefix = file_exists("$basedir/custom.event-listeners.json") ?
            'custom' : 'default';
        $json = file_get_contents("$basedir/${prefix}.event-listeners.json");
        $data  = json_decode($json, true);
        $this->data = $data;
        $this->setHydrator(new ArraySerializable());
        $this->setAllowedObjectBindingClass(ArrayObject::class);        
        foreach ($data as $event_name => $event_array) {
            $fieldset = new ConfigFieldset($event_name, ['config' => $event_array, 'disabled' => $this->disabled]);
            $this->add($fieldset);
        }
    }

    /**
     * gets  inputfilter specification
     *
     * @return Array
     */
    public function getInputFilterSpecification()
    {
        return [];
    }
}
