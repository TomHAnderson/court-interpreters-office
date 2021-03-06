<?php /** module/Admin/src/Controller/ConfigController.php */

namespace InterpretersOffice\Admin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\InputFilter;

/**
 * Configuration controller
 *
 * Controller for admin UI to optional form configuration.
 */
class ConfigController extends AbstractActionController
{

    /**
     * path to configuration file
     *
     * @var string
     */
    private $form_config_path = 'module/Admin/config/forms.json';

    /**
     * permissions
     *
     * @var array
     */
    private $permissions;

    /**
     * constructor
     *
     * @param array $permissions to be injected into view
     */
    public function __construct(Array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * index page
     *
     * @return array
     */
    public function indexAction() : array
    {
        return [];
    }

    /**
     * form configuration page
     *
     * @return array
     */
    public function formsAction() : array
    {
        $error = false;
        if (! file_exists($this->form_config_path)) {
            $error = 'Unable to find form configuration file.';
        } elseif (! is_writable($this->form_config_path)) {
            $error = 'Form configuration file is not writeable.';
        } else {
            $data = file_get_contents($this->form_config_path);
            $config = json_decode($data);
            if (! $data) {
                $error = 'Form configuration file could not be parsed.';
            }
        }
        if ($error) {
            return ['errorMessage' => $error ];
        }
        return ['config' => $config ] + $this->permissions;
    }

    /**
     * gets input filter
     *
     * @return InputFilter\InputFilter
     */
    public function getInputFilter()
    {
        $interpreterFormFilter = new InputFilter\InputFilter();
        foreach (['banned_by_persons', 'BOP_form_submission_date', 'fingerprint_date',
        'contract_expiration_date','oath_date','security_clearance_date'] as $field) {
            $interpreterFormFilter->add([
                'name' => $field,
                'required' => true,
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => ['isEmpty' => "$field field is required"],
                        ],
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => 'InArray',
                        'options' => [
                            'haystack' => [ '0', '1' ],
                            'messages' => [
                                'notInArray' => "invalid value for $field"
                            ],
                        ],
                        'break_chain_on_failure' => true,
                    ],
                ],
            ]);
        }

        $eventFormFilter = new InputFilter\InputFilter();
        $field = 'end time';
        $eventFormFilter->add([
            'name' => 'end_time',
            'required' => true,
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => ['isEmpty' => "$field field is required"],
                    ],
                    'break_chain_on_failure' => true,
                ],
                [
                    'name' => 'InArray',
                    'options' => [
                        'haystack' => [ '0', '1' ],
                        'messages' => [
                            'notInArray' => "invalid value for $field"
                        ],
                    ],
                    'break_chain_on_failure' => true,
                ],
            ],
        ]);
        $inputFilter = new InputFilter\InputFilter();
        $inputFilter->add($interpreterFormFilter, 'interpreters');
        $inputFilter->add($eventFormFilter, 'events');
        $inputFilter->add([
            'name' => 'csrf',
            'validators' => [
                [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'required security token is missing'
                        ],
                    ],
                    'break_chain_on_failure' => true,
                ],
                [
                    'name' => 'Csrf',
                    'options' => [
                        'messages' => [
                            'notSame' =>
                            'Invalid or expired security token. Please reload this page and try again.'
                        ]],
                ],
            ],
        ]);

        return $inputFilter;
    }

    /**
     * handles POST data
     *
     * @return JsonModel
     */
    public function postAction()
    {

        $inputFilter = $this->getInputFilter();
        $inputFilter->setData($this->params()->fromPost());
        if (! $inputFilter->isValid()) {
            return new JsonModel(['validation_errors' => $inputFilter->getMessages()]);
        }
        $data = $inputFilter->getValues();
        $array = [
            'interpreters' => ['optional_elements' => $data['interpreters']],
            'events' => ['optional_elements' => $data['events']],
        ];
        $json = \json_encode($array, \JSON_PRETTY_PRINT);
        \file_put_contents($this->form_config_path, $json);
        return new JsonModel([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
