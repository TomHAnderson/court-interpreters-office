<?php /** module/Admin/src/Service/DocketAnnotationService.php */

declare(strict_types=1);

namespace InterpretersOffice\Admin\Service;

use InterpretersOffice\Service\ObjectManagerAwareTrait;
use InterpretersOffice\Entity;
use Doctrine\ORM\EntityManagerInterface;

use Laminas\InputFilter\Factory;
use Laminas\InputFilter;
use Laminas\Validator;
use Laminas\Filter;
use Laminas\Authentication\AuthenticationServiceInterface;
use Parsedown;
use InterpretersOffice\Admin\Form\Validator\Docket as DocketValidator;
use InterpretersOffice\Admin\Form\Filter\Docket as DocketFilter;

/**
 * docket-annotation management
 */
class DocketAnnotationService
{

    use MarkdownTrait;

    /**
     * entity manager
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * authentication service
     *
     * @var AuthenticationServiceInterface
     */
    private $auth;


    public function __construct(EntityManagerInterface $em,
        AuthenticationServiceInterface $auth)
    {
        $this->em = $em;
        $this->auth = $auth;
    }

    /**
     * input filter
     *
     * @var InputFilter
     */
    private $filter;

    public function createInputFilter()
    {
        return (new InputFilter\Factory)->createInputFilter([
            'csrf' => [
                'name' => 'csrf',
                'required' => true,
                'validators' => [
                    [
                        'name' => Validator\NotEmpty::class,
                        'options' => [
                            'messages' => [
                                Validator\NotEmpty::IS_EMPTY => "required security token is missing",
                            ]
                        ],
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => Validator\Csrf::class,
                        'options' => [
                            'messages' =>
                                [
                                    'notSame' => 'Security error: invalid/expired CSRF token.'
                                    .' Please reload the page and try again.',
                                ],
                            'timeout' => 1200,
                        ],
                    ],
                ]
            ],
            'priority' => [
                'name' => 'priority',
                'required' => true,
                'validators' => [
                    [
                        'name' => Validator\NotEmpty::class,
                        'options' => [
                            'messages' => [
                                Validator\NotEmpty::IS_EMPTY => "priority is required",
                            ]
                        ],
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => Validator\InArray::class,
                        'options' => [
                            'haystack' => [1,2,3],
                            'messages' => [
                                Validator\InArray::NOT_IN_ARRAY =>
                                 'priority must be either 1, 2 or 3',
                            ],
                        ],
                        'break_chain_on_failure' => true,
                    ],
                ],
                'filters' => [

                ],
            ],
            'docket' => [
                'name' => 'docket',
                'required' => true,
                'validators' => [
                    [
                        'name' => Validator\NotEmpty::class,
                        'options' => [
                            'messages' => [
                                Validator\NotEmpty::IS_EMPTY => "docket number is required",
                            ]
                        ],
                    ],
                    [
                        'name'=> DocketValidator::class,
                    ],
                ],
                'filters' => [
                    [
                        'name' => Filter\StringTrim::class,
                    ],
                    [
                        'name' => DocketFilter::class,
                    ]
                ],
            ],
            'comment' => [
                'name' => 'comment',
                'required' => true,
                'validators' => [
                    [
                        'name' => Validator\NotEmpty::class,
                        'options' => [
                            'messages' => [
                                Validator\NotEmpty::IS_EMPTY => "some comment text is required",
                            ]
                        ],
                        'break_chain_on_failure' => true,
                    ],
                    [
                        'name' => Validator\StringLength::class,
                        'options' => [
                            'min' => 5, 'max' => 600,
                            'messages' => [
                                 Validator\StringLength::TOO_SHORT => 'comment has to be a minimum of %min% characters',
                                 Validator\StringLength::TOO_LONG => 'comment cannot exceed a maximum of %max% characters',
                            ]
                        ]
                    ]
                ],
                'filters' => [
                    [
                        'name' => Filter\StringTrim::class,
                    ],
                ],
            ],
        ]);
    }

    /**
     * gets input filter
     *
     * @return
     */
    public function getInputFilter()
    {
        if (! $this->filter) {
            $this->filter = $this->createInputFilter();
        }
        return $this->filter;
    }

    /**
     * gets DocketAnnotation
     *
     * @param  int    $id
     * @return Entity\DocketAnnotation
     */
    public function get(int $id) :? Entity\DocketAnnotation
    {
        $repo = $this->em->getRepository(Entity\DocketAnnotation::class);
        return $repo->find($id);
    }

    /**
     * gets annotations for $docket
     * @param  string $docket
     * @return Array
     */
    public function getAnnotations(string $docket) : Array
    {
        //var_dump(class_exists(Entity\DocketAnnotation::class));exit();
        $repo = $this->em->getRepository(Entity\DocketAnnotation::class);
        // we need to write our own repo and optimize this query
        return $repo->findByDocket($docket);
    }

    public function create(array $data) : array
    {
        $filter = $this->getInputFilter();
        $filter->setData($data);
        if (! $filter->isValid()) {
            return ['validation_errors' => $filter->getMessages(), 'status'=>'validation failed'];
        }
        return [ 'status' => 'so far so good', 'valid' => true];
    }

    public function update(array $data, $id)
    {

    }

    public function delete()
    {

    }


}
