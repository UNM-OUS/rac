<?php
namespace Digraph\Modules\rac_proposals\Fields;

use Digraph\CMS;
use Digraph\Modules\ous_digraph_module\Fields\College;
use Formward\FieldInterface;
use Formward\Fields\Email;
use Formward\Fields\Input;
use Formward\Fields\Number;
use Formward\Fields\Phone;
use Formward\Fields\Select;
use Formward\Fields\Textarea;

class SubmitterField2019 extends \Digraph\Modules\Submissions\SubmitterField
{
    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms = null)
    {
        parent::__construct($label, $name, $parent);
        $this['firstname'] = new Input('First name');
        $this['firstname']->required(true);
        $this['lastname'] = new Input('Last name');
        $this['lastname']->required(true);
        $this['college'] = new College('College/School');
        $options = $this['college']->options();
        unset($options['Other/Administrative']);
        unset($options['College of Nursing']);
        unset($options['College of Pharmacy']);
        unset($options['College of Population Health']);
        unset($options['School of Law']);
        unset($options['School of Medicine']);
        $this['college']->options($options);
        $this['college']->required(true);
        $this['college']->addTip('Only main and branch campus voting faculty are eligible.');
        $this['department'] = new Input('Department');
        $this['department']->required(true);
        $this['address'] = new Textarea('Campus Address');
        $this['address']->required(true);
        $this['address']->attr('rows', 2);
        $this['address']->attr('style', 'height:auto;');
        $this['email'] = new Email('Email address');
        $this['email']->required(true);
        $this['phone'] = new Phone('Daytime phone number');
        $this['phone']->required(true);
        $this['rank'] = new Select('Faculty rank');
        $this['rank']->options([
            "Assistant Professor" => "Assistant Professor",
            "Associate Professor" => "Associate Professor",
            "Professor" => "Professor",
            "Distinguished Professor" => "Distinguished Professor",
            "Lecturer" => "Lecturer",
        ]);
        $this['rank']->required(true);
        $this['yearsvoting'] = new Number('Number of years as voting faculty at UNM');
        $this['yearsvoting']->required(true);
    }
}
