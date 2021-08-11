<?php
namespace Digraph\Modules\rac_proposals\Fields;

use Digraph\CMS;
use Formward\FieldInterface;
use Formward\Fields\Input;
use Formward\Fields\Number;
use Formward\Fields\Select;

class SubmissionField2019 extends \Digraph\Modules\Submissions\SubmissionField
{
    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms = null)
    {
        parent::__construct($label, $name, $parent);
        //discipline
        $this['discipline'] = new Select('Primary discipline area');
        $this['discipline']->options([
            "Physical Sciences" => "Physical Sciences — e.g. chemistry, earth and planetary, mathematics and statistics, physics and astronomy.",
            "Life Sciences" => "Life Sciences — e.g. biology, psychology.",
            "Social Sciences" => "Social Sciences — e.g. anthropology, business and administrative sciences, economics, geography, history, law, political science, sociology.",
            "Engineering" => "Engineering — all departments of the School of Engineering.",
            "Education" => "Education — all departments of the College of Education.",
            "Humanities" => "Humanities — e.g. architecture, English, journalism, foreign languages and literatures, Spanish and Portuguese, philosophy, communication.",
            "Fine Arts" => "Fine Arts — all departments of the College of Fine Arts.",
        ]);
        $this['discipline']->required(true);
        //title
        $this['title'] = new Input("Title of Proposed Research/Creative Work");
        $this['title']->required(true);
        //amount requested
        $this['requested'] = new Number("Total amount of RAC funds requested");
        $this['requested']->required(true);
    }

    public function setMinimum($amount)
    {
        $this['requested']->addTip('Minimum $' . number_format($amount));
        $this['requested']->addValidatorFunction('minimumvalue', function ($field) use ($amount) {
            if ($field->value() && $field->value() < $amount) {
                return "Minimum: $" . number_format($amount);
            }
            return true;
        });
    }

    public function setMaximum($amount)
    {
        $this['requested']->addTip('Maximum $' . number_format($amount));
        $this['requested']->addValidatorFunction('maximumvalue', function ($field) use ($amount) {
            if ($field->value() && $field->value() > $amount) {
                return "Maximum: $" . number_format($amount);
            }
            return true;
        });
    }
}
