<?php
/**
 * module/Admin/src/Form/InterpreterFieldset.php.
 */

namespace InterpretersOffice\Admin\Form;

use InterpretersOffice\Form\PersonFieldset;
use Doctrine\Common\Persistence\ObjectManager;
use InterpretersOffice\Entity;

// experimental
use Laminas\Form\Element;

/**
 * InterpreterFieldset.
 *
 * @author david
 */
class InterpreterFieldset extends PersonFieldset
{
    /**
     * name of the fieldset.
     *
     * since we are a subclass of PersonFieldset, this needs to be overriden
     *
     * @var string
     */
    protected $fieldset_name = 'interpreter';

    /**
     * configuration options
     *
     * @var Array options
     */
    protected $options;

   /**
     * encrypted field values
     *
     * encrypted ssn and dob are stored here so we can compare
     * and determine whether to apply validation
     *
     * @var Array
     */
    protected $original_encrypted_values;

    /**
     * Optional form elements
     *
     * Form elements that can be enabled or disabled through
     * admin configuration UI.
     *
     * @var array
     */
    private $optional_elements = [

        'fingerprint_date' => [
            'name' => 'fingerprint_date',
            'type' => 'Laminas\Form\Element\Text',
            'attributes' => [
                'id' => 'fingerprint_date',
                'class' => 'date form-control',
            ],
             'options' => [
                'label' => 'fingerprinted on',
                'format' => 'Y-m-d',
             ],
         ],
         'BOP_form_submission_date' => [
             'name' => 'BOP_form_submission_date',
             'type' => 'Laminas\Form\Element\Text',
             'attributes' => [
                 'id' => 'BOP_form_submission_date',
                 'class' => 'date form-control',
                 'placeholder' => 'date BOP security clearance form was submitted',
             ],
              'options' => [
                 'label' => 'BOP form submitted',
                 'format' => 'Y-m-d',
              ],
         ],
         'oath_date' => [
             'name' => 'oath_date',
             'type' => 'Laminas\Form\Element\Text',
             'attributes' => [
                 'id' => 'oath_date',
                 'class' => 'date form-control',
             ],
              'options' => [
                 'label' => 'oath taken',
                 'format' => 'Y-m-d',
             ],
         ],
         'security_clearance_date' => [
            'name' => 'security_clearance_date',
            'type' => 'Laminas\Form\Element\Text',
            'attributes' => [
                'id' => 'security_clearance_date',
                'class' => 'date form-control',
                'placeholder' => 'date security clearance was received',
            ],
             'options' => [
                'label' => 'security clearance date',
                'format' => 'Y-m-d',
             ],
         ],
         'contract_expiration_date' => [
             'name' => 'contract_expiration_date',
             'type' => 'Laminas\Form\Element\Text',
             'attributes' => [
                 'id' => 'contract_expiration_date',
                 'class' => 'date form-control',
             ],
              'options' => [
                 'label' => 'contract expires',
                 'format' => 'Y-m-d',
              ],
          ],
          'banned_by_persons' => [
              'name' => 'banned_by_persons',
              'type' => 'Laminas\Form\Element\Select',
              'options' => [
                  'value_options' => [],
                  'disable_inarray_validator' => true,
              ],
              'attributes' => [
                  'style' => 'display:none',
                  'id' => 'banned_by_persons',
                  'multiple' => 'multiple',
              ],
          ],
    ];

    /**
     * constructor.
     *
     * @param ObjectManager $objectManager
     * @param array         $options
     */
    public function __construct(ObjectManager $objectManager, $options = [])
    {
        parent::__construct($objectManager, $options);
        $this->options = $options;
        $this->add([
            'type' => Element\Collection::class,
            'name' => 'interpreterLanguages',
            'options' => [
                'label' => 'working languages',
                'target_element' => new InterpreterLanguageFieldset($objectManager),
            ],
        ]);
        $this->add(
            new \InterpretersOffice\Form\Element\LanguageSelect(
                'language-select',
                [
                    'type' => 'InterpretersOffice\Form\Element\LanguageSelect',
                    'name' => 'language-select','objectManager' => $objectManager,
                    'options' => [
                        'objectManager' => $objectManager,
                        'option_attributes' =>
                            ['data-certifiable' =>
                                function (Entity\Language $language) {
                                    return $language->isFederallyCertified() ? 1 : 0;
                                }
                             ],
                        'empty_item_label' => '--- add a language ---',
                        'exclude' => true,// doesn't work?
                    ],
                ]
            )
        );
        if (! empty($options['optional_elements'])) {
            foreach ($this->optional_elements as $name => $spec) {
                if (! empty($options['optional_elements'][$name])) {
                    $this->add($spec);
                }
            }
        }

        // home phone
        $this->add([
            'name' => 'home_phone',
            'type' => 'Laminas\Form\Element\Text',
            'attributes' => [ 'id' => 'home_phone', 'class' => 'form-control phone'],
            'options' => [ 'label' => 'home phone'],
        ]);

        $this->add([
            'name' => 'comments',
            'type' => 'Laminas\Form\Element\Textarea',
            'attributes' => [
                'id' => 'comments', 'class' => 'form-control',
                'rows' => 10,
                'cols'  => 36,
             ],
            'options' => [ 'label' => 'comments'],
        ]);

        $this->addAddressElements();

        if ($options['vault_enabled']) {
            // complicated stuff
            $this->add(
                [
                'name' => 'dob',
                'type' => 'Laminas\Form\Element\Text',
                'attributes' => ['id' => 'dob','class' => 'form-control encrypted date'],
                'options' => [
                    'label' => 'date of birth',
                    //'format' => 'Y-m-d',
                    ],
                ]
            );
            $this->add(
                [
                'name' => 'ssn',
                'type' => 'Laminas\Form\Element\Text',
                'attributes' => ['id' => 'ssn','class' => 'form-control encrypted'],
                'options' => ['label' => 'social security no.'],
                ]
            );
        }
        $this->add([
            'name' => 'solicit_availability',
            'type' => 'checkbox',
            'attributes' => [
                'value' => 1,
                'id' => 'solicit_availability',
                //'class' => 'form-check-input',
            ],
            'options' => [
            'label' => 'solicit availability',
            'use_hidden_element' => true,
            'checked_value' => 1,
            'unchecked_value' => 0,],
        ]);
        $this->add([
            'name' => 'publish_public',
            'type' => 'checkbox',
            'attributes' => [
                'value' => 1,
                'id' => 'publish_public',
                //'class' => 'form-check-input',
            ],
            'options' => [
            'label' => 'publish public',
            'use_hidden_element' => true,
            'checked_value' => 1,
            'unchecked_value' => 0,],
        ]);
        /*
        $this->add([
            'type' => 'Laminas\Form\Element\Checkbox',
            'name' => 'active',
            'required' => true,
            'allow_empty' => false,
            'options' => [
                'label' => 'active',
                'use_hidden_element' => true,
                'checked_value' => 1,
                'unchecked_value' => 0,
            ],
            'attributes' => [
                'value' => 1,
                'id' => 'user-active',
            ],
        ]);
         */
    }

    /**
     * adds the specialized "Hat" element to the form.
     *
     * @return \InterpretersOffice\Admin\Form\InterpreterFieldset
     */
    public function addHatElement()
    {
        $this->add([
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            'name' => 'hat',
            'options' => [
                'object_manager' => $this->objectManager,
                'target_class' => 'InterpretersOffice\Entity\Hat',
                'property' => 'name',
                'find_method' => ['name' => 'getInterpreterHats'],
                'label' => 'hat',
            ],
             'attributes' => [
                'class' => 'form-control',
                'id' => 'hat',
             ],
        ]);
        // hack designed to please HTML5 validator
        $element = $this->get('hat');
        $options = $element->getValueOptions();
        array_unshift($options, [
           'label' => ' ',
           'value' => '',
           'attributes' => [
               'label' => ' ',
           ],
        ]);
        $element->setValueOptions($options);

        return $this;
    }
    /**
     * adds address elements
     */
    public function addAddressElements()
    {
        // address 1
        $this->add([
            'name' => 'address1',
            'type' => 'Laminas\Form\Element\Text',
            'attributes' => [ 'id' => 'address1', 'class' => 'form-control'],
            'options' => [ 'label' => 'address (1)',],
        ]);
        // address 2
        $this->add([
            'name' => 'address2',
            'type' => 'Laminas\Form\Element\Text',
            'attributes' => [ 'id' => 'address2', 'class' => 'form-control',],
            'options' => [ 'label' => 'address (2)'],
        ]);

        // city
        $this->add([
            'name' => 'city',
            'type' => 'Laminas\Form\Element\Text',
            'attributes' => [ 'id' => 'city', 'class' => 'form-control'],
            'options' => [ 'label' => 'city'],
        ]);

        // state or province
        $this->add([
            'name' => 'state',
            'type' => 'Laminas\Form\Element\Text',
            'attributes' => [ 'id' => 'state', 'class' => 'form-control'],
            'options' => [ 'label' => 'state'],
        ]);
        // zip/postal code
        $this->add([
            'name' => 'zip',
            'type' => 'Laminas\Form\Element\Text',
            'attributes' => [ 'id' => 'zip', 'class' => 'form-control'],
            'options' => [ 'label' => 'zip/postal code'],
        ]);
        // country
        $this->add([
            'name' => 'country',
            'type' => 'Laminas\Form\Element\Text',
            'attributes' => [ 'id' => 'country', 'class' => 'form-control'],
            'options' => [ 'label' => 'country'],
        ]);
    }

    /**
     * overrides parent implementation of InputFilterProviderInterface.
     *
     * @return array
     */
    public function getInputFilterSpecification()
    {
        $spec = parent::getInputFilterSpecification();
        $spec['interpreterLanguages'] = [

            'allow_empty' => false,
            'required' => true,
            'validators' => [
                 [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'at least one language is required',
                        ],
                    ],
                    'break_chain_on_failure' => true,
                 ],
                // this is bullshit
                 [
                    'name' => 'Callback',
                    'options' => [
                        'callback' => function ($value, $context) {
                            $languages_submitted = $context['interpreterLanguages'];
                            foreach ($languages_submitted as $language) {
                                $id = $language['language'];
                                if (empty($language['languageCredential'])) {
                                    return false;
                                }
                            }
                            return true;
                        },
                        'messages' => [
                            \Laminas\Validator\Callback::INVALID_VALUE => 'credential is required',
                        ],
                    ],
                 ],
            ],
        ];
        // this one is just for the UI, not part of the entity's data
        $spec['language-select'] = [
            'required' => true,
            'allow_empty' => true,
         ];
        // */
         $spec['hat'] = [
                'validators' => [
                    [
                    'name' => 'NotEmpty',
                    'options' => [
                        'messages' => [
                            'isEmpty' => 'hat is required (contract or staff)',
                        ],
                    ],
                    'break_chain_on_failure' => true,
                    ],
                ],
         ];
         $spec['solicit_availability'] =
             [
                 'required' => false, // for now
                 'allow_empty' => true,
                 'validators' => [],
                 'filters' => [],

            ];
         $spec['publish_public'] =
            [
                'required' => false, // for now
                'allow_empty' => true,
                'validators' => [],
                'filters' => [],

           ];

         /** optional fields */

         if ($this->has('fingerprint_date')) {
             $spec['fingerprint_date'] = [
                 'allow_empty' => true,
                 'required' => false,
                 'filters' => [
                     [
                         'name' => 'StringTrim',
                     ],
                 ],
                 'validators' => [
                     [
                         'name' => 'Laminas\Validator\Date',
                         'options' => [
                             'format' => 'm/d/Y',
                             'messages' => [\Laminas\Validator\Date::INVALID_DATE => 'valid date in MM/DD/YYYY format is required']
                         ],
                         'break_chain_on_failure' => true,
                     ],
                     [
                         'name' => 'Callback',
                         'options' => [
                             'callback' => function ($value, $context) {
                                 // it can't be in the future
                                 list($M, $D, $Y) = explode('/', $value);
                                 $date = "$Y-$M-$D";
                                 $max = date('Y-m-d');
                                 // $min = (new \DateTime("-3 years"))->format('Y-m-d');
                                 return $date <= $max;
                             },
                             'messages' => [
                                 \Laminas\Validator\Callback::INVALID_VALUE => 'fingerprint date cannot be set to a future date',
                             ],
                         ],
                     ],
                 ],
             ];
         }
         if ($this->has('security_clearance_date')) {
             $spec['security_clearance_date'] = [
             'allow_empty' => true,
             'required'  => false,
             'filters' => [
                [
                    'name' => 'StringTrim',
                ],
              ],
             'validators' => [
                 [
                    'name' => 'Laminas\Validator\Date',
                    'options' => [
                        'format' => 'm/d/Y',
                         'messages' => [\Laminas\Validator\Date::INVALID_DATE => 'valid date in MM/DD/YYYY format is required']
                    ],
                    'break_chain_on_failure' => true,
                 ],
                 [
                    'name' => 'Callback',
                    'options' => [
                            'callback' => function ($value, $context) {
                                // it can't be in the future
                                // and it can't be unreasonably long ago
                                list($M, $D, $Y) = explode('/', $value);
                                $date = "$Y-$M-$D";
                                $max = date('Y-m-d');
                                $min = (new \DateTime("-5 years"))->format('Y-m-d');
                                return $date >= $min && $date <= $max;
                            },
                            'messages' => [
                                \Laminas\Validator\Callback::INVALID_VALUE => 'date has to be between five years ago and today',
                            ],
                        ],
                    ],
                ],
             ];
         }
         if ($this->has('contract_expiration_date')) {
             $spec['contract_expiration_date'] = [
                'allow_empty' => true,
                'required' => false,
                'filters' => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
                'validators' => [
                    [
                        'name' => 'Laminas\Validator\Date',
                        'options' => [
                            'format' => 'm/d/Y',
                            'messages' => [\Laminas\Validator\Date::INVALID_DATE => 'valid date in MM/DD/YYYY format is required']
                        ],
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => 'Callback',
                        'options' => [
                            'callback' => function ($value, $context) {
                                list($M, $D, $Y) = explode('/', $value);
                                $date = "$Y-$M-$D";
                                $max = (new \DateTime("+2 years"))->format('Y-m-d');
                                $min = (new \DateTime("-5 years"))->format('Y-m-d');
                                return $date >= $min && $date <= $max;
                            },
                            'messages' => [
                                \Laminas\Validator\Callback::INVALID_VALUE => 'date has to be between five years ago and today',
                            ],
                        ],
                    ],
                ],
             ];
         }
         if ($this->has('oath_date')) {
             $spec['oath_date'] = [
                'allow_empty' => true,
                'required'  => false,
                'filters' => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
                'validators' => [
                    [
                        'name' => 'Laminas\Validator\Date',
                        'options' => [
                            'format' => 'm/d/Y',
                            'messages' => [\Laminas\Validator\Date::INVALID_DATE => 'valid date in MM/DD/YYYY format is required']
                        ],
                        'break_chain_on_failure' => true,
                    ],
                    ///*
                    [ 'name' => 'Callback',
                    'options' => [
                        'callback' => function ($value, $context) {
                            // it can't be in the future
                            // and it can't be unreasonably long ago
                            list($M, $D, $Y) = explode('/', $value);
                            $date = "$Y-$M-$D";
                            $max = (new \DateTime())->format('Y-m-d');
                            // $min = (new \DateTime("-6 years"))->format('Y-m-d');
                            return $date <= $max;
                        },
                        'messages' => [
                            \Laminas\Validator\Callback::INVALID_VALUE => 'oath date cannot be set in the future',
                        ],
                    ],
                    ],
                ],
             ];
         }
         if ($this->has('BOP_form_submission_date')) {
             $spec['BOP_form_submission_date'] = [
                'allow_empty' => true,
                'required'  => false,
                'filters' => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
                'validators' => [
                    [
                        'name' => 'Laminas\Validator\Date',
                        'options' => [
                            'format' => 'm/d/Y',
                            'messages' => [\Laminas\Validator\Date::INVALID_DATE => 'valid date in MM/DD/YYYY format is required']
                        ],
                        'break_chain_on_failure' => true,
                    ],
                    ///*
                    [ 'name' => 'Callback',
                    'options' => [
                        'callback' => function ($value, $context) {
                            // it can't be in the future
                            list($M, $D, $Y) = explode('/', $value);
                            $date = "$Y-$M-$D";
                            $max = (new \DateTime())->format('Y-m-d');
                            return $date <= $max;
                        },
                        'messages' => [
                            \Laminas\Validator\Callback::INVALID_VALUE => 'form submission date cannot be set in the future',
                        ],
                    ],
                    ],
                ],
             ];
         }
        /** @todo tighten this up? */
         if ($this->has('banned_by_persons')) {
             $spec['banned_by_persons'] = [
                'required' => false,
                'allow_empty' => true,
             ];
         }
         // encrypted fields
            $spec['dob'] = [
             'allow_empty' => true,
             'required'  => false,
              'filters' => [
                [
                    'name' => 'StringTrim',
                ],
              ],
             'validators' => [
                 [
                    'name' => 'Laminas\Validator\Date',
                    'options' => [
                        'format' => 'Y-m-d',
                         'messages' => [\Laminas\Validator\Date::INVALID_DATE => 'valid date in MM/DD/YYYY format is required']
                    ],
                    'break_chain_on_failure' => true,
                 ],
                 [ 'name' => 'Callback',
                    'options' => [
                        'callback' => function ($date, $context) {
                            // it can't be in the future
                            // and it can't be unreasonably long ago
                            $max = (new \DateTime("-18 years"))->format('Y-m-d');
                            $min = (new \DateTime("-100 years"))->format('Y-m-d');
                            return $date >= $min && $date <= $max;
                        },
                        'messages' => [
                            \Laminas\Validator\Callback::INVALID_VALUE => 'date of birth has to be between 18 and 100 years ago',
                        ],
                    ],
                 ],
             ],
             'filters' => [
                    [
                        'name' => 'StringTrim',
                    ],
                    [
                       'name' => 'Callback',
                        'options' => [
                            'callback' => function ($value) {
                                if (! preg_match('|^\d\d/\d\d/\d{4}$|', $value)) {
                                    return $value;
                                }
                                list($M, $D, $Y) = explode('/', $value);
                                return "$Y-$M-$D";
                            },
                        ],
                    ],
                ],
            ];
            $spec['ssn'] = [
             'allow_empty' => true,
             'required'  => false,
              'validators' => [
                [
                    'name' => 'StringLength',
                    'options' => [
                        'min' => 9,
                        'max' => 9,
                         'messages' => [
                                \Laminas\Validator\StringLength::TOO_SHORT => 'ssn must contain nine digits',
                                \Laminas\Validator\StringLength::TOO_LONG => 'ssn number cannot exceed nine digits',
                         ],
                    ],
                ],
              ],
             'filters' => [
                ['name' => 'StringTrim'],
                ['name' => 'Digits', ],
             ],
            ];

            $spec['home_phone'] = [
             'allow_empty' => true,
             'required'  => false,
              'validators' => [
                    $this->phone_validator_spec,
                ],
                'filters' => [
                    ['name' => 'StringTrim'],
                    ['name' => 'Digits', ],
                ],
            ];
         /*
          * `contract_expiration_date` date DEFAULT NULL,
            `comments` varchar(600) COLLATE utf8_unicode_ci NOT NULL,
            `address1` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
            `address2` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
            `city` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
            `state` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
            `zip` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
            `country` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
          */
         // address data
            $spec['address1'] = [
             'allow_empty' => true,
             'required'  => false,
              'filters' => [
                    [ 'name' => \Laminas\Filter\StringTrim::class ]
              ],
              'validators' => [
                [
                    'name' => \Laminas\Validator\StringLength::class,
                    'options' => [
                        'max' => 40,
                        'messages' => [
                        \Laminas\Validator\StringLength::TOO_LONG =>
                            'address exceeds maximum length of 40 characters'
                        ]
                    ]
                ]
              ]
            ];
            $spec['address2'] = [
            'allow_empty' => true,
            'required'  => false,
            'filters' => [
                [ 'name' => \Laminas\Filter\StringTrim::class ]
             ],
            'validators' => [
                [
                    'name' => \Laminas\Validator\StringLength::class,
                    'options' => [
                        'max' => 40,
                        'messages' => [
                            \Laminas\Validator\StringLength::TOO_LONG =>
                            'address exceeds maximum length of 40 characters'
                        ]
                    ]
                ],
            ],//validators
            ];
            $spec['city'] = [
             'allow_empty' => true,
             'required'  => false,
            ];
            $spec['state'] = [
             'allow_empty' => true,
             'required'  => false,
            ];
            $spec['zip'] = [
             'allow_empty' => true,
             'required'  => false,
            ];
            $spec['country'] = [
             'allow_empty' => true,
             'required'  => false,
            ];
            $spec['comments'] = [
             'allow_empty' => true,
             'required'  => false,
              'filters' => [
                   [ 'name' => \Laminas\Filter\StringTrim::class ]
              ],
              'validators' => [
                    [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'max' => 600,
                            'messages' => [
                            \Laminas\Validator\StringLength::TOO_LONG =>
                                'comments exceed maximum length of 600 characters'
                            ]
                        ]
                    ]
              ]
            ];
            $spec['email']['allow_empty'] = false;
            return $spec;
    }
}
