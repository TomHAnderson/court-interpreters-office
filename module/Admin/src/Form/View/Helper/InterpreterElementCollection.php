<?php /** module/Admin/src/Form/View/Helper/InterpreterElementCollection.php */

namespace InterpretersOffice\Admin\Form\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\Form\Element\Collection as ElementCollection;

/**
 * helper for rendering interpreters
 */
class InterpreterElementCollection extends AbstractHelper
{
    /**
     * markup template
     *
     * @var string
     */
    protected $template = <<<TEMPLATE
        <li class="list-group-item py-1 interpreter-assigned pr-1">
            <input name="event[interpreterEvents][%d][interpreter]" type="hidden" value="%d">
            <input name="event[interpreterEvents][%d][event]" type="hidden" value="%d">
            <span class="align-middle pt-1">%s</span>
            <button class="btn btn-warning btn-sm btn-remove-item float-right border" title="remove this interpreter">
            <span class="fas fa-times" aria-hidden="true"></span>
            <span class="sr-only">remove this interpreter
            </button>
        </li>
TEMPLATE;

// we removed <!-- <input name="event[interpreterEvents][%d][created_by]" type="hidden" value="%d"> -->
// <input name="event[interpreterEvents][%d][name]" type="hidden" value="%s">

    /**
     * invoke
     *
     * @param ElementCollection $collection
     * @return string
     */
    public function __invoke(ElementCollection $collection)
    {
        return $this->render($collection);
    }

    /**
     * renders markup
     *
     * @param Collection $collection
     * @return string
     */
    public function render(ElementCollection $collection)
    {
        if (! $collection->count()) {
            return '';
        }
        // to do: deal with possible undefined $form
        $form = $this->getView()->form;
        $entity = $form->getObject();
        $interpreterEvents = $entity->getInterpreterEvents();
        $markup = '';
        foreach ($interpreterEvents as $i => $interpEvent) {
            $interpreter = $interpEvent->getInterpreter();
            $event = $interpEvent->getEvent();
            $name = $interpreter->getLastname().', '.$interpreter->getFirstName();
            // 5 placeholders, yes somewhat excessive
            $markup .= sprintf(
                $this->template,
                $i,
                $interpreter->getId(),
                $i,
                $event->getId(),
                $name
            );
        }
        return $markup;
    }

    /**
     * gets template
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * renders InterpreterEvent fieldset from array data
     *
     * @param array $data
     * @return string
     */
    public function fromArray(array $data)
    {
        if (! isset($data['name'])) {
            $data['name'] = '__NAME__';
        }
        $markup = sprintf(
            $this->template,
            $data['index'],
            $data['interpreter_id'],
            $data['index'],
            $data['event_id'],
            //$data['index'],
            //$data['name'],
            $data['name']
        );
        return $markup;
    }

    /**
     * renders interpreter-events from POST data
     *
     * this simply translates the names of the array
     * keys and calls fromArray()
     *
     * @param array $data
     * @return string
     */
    public function fromPost(array $data)
    {
        $markup = '';
        foreach ($data as $index => $ie) {
            $markup .= $this->fromArray(
                [
                    'index' => $index,
                    'name'  => $ie['name'],
                    //'created_by' => $ie['created_by'],
                    'event_id'  => $ie['event'],
                    'interpreter_id' => $ie['interpreter']
                ]
            );
        }
        return $markup;
    }

    /**
     * input filter spec for xhr/interpreter template helper
     *
     * @return Array
     */
    public function getInputFilterSpecification()
    {
         return
         [
            'interpreter_id' => [
                'name' => 'interpreter_id',
                'required' => true,
                'allow_empty' => false,
                'validators' => [
                    ['name' => 'Digits'],
                ],
            ],
              'event_id' => [
                'name' => 'event_id',
                'required' => true,
                'allow_empty' => true,
                'validators' => [
                    ['name' => 'Digits'],
                ],
              ],
            'index' => [
                'name' => 'index',
                'required' => true,
                'allow_empty' => false,
                'validators' => [
                    ['name' => 'Digits'],
                ]
            ],
            'name' => [
                'name' => 'name',
                'required' => false,
                'allow_empty' => true,
                'validators' => [
                    [ 'name' => 'StringLength',
                        'options' => [
                            'max' => 152,
                            'min' => 5,
                        ],
                    ],
                ],
            ],
         ];
    }
}
