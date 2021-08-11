<?php
namespace Digraph\Modules\rac_admin\Ratings;

use Formward\Fields\Checkbox;
use Formward\Fields\Container;

class Rating2020Fall extends AbstractRating
{
    public function body($showName = false): string
    {
        $out = '<h3>';
        if ($showName) {
            if ($member = $this->prop->cms()->helper('rac')->member($this->netid)) {
                $member = $member->name();
            } else {
                $member = $this->netid;
            }
            $out .= $member;
        } else {
            $rid = static::$reviewerIDs[$this->netid];
            $out .= "Reviewer #$rid";
            if ($this->prop->cms()->helper('permissions')->check('proposal/view', 'rac')) {
                if ($member = $this->prop->cms()->helper('rac')->member($this->netid)) {
                    $member = $member->name();
                } else {
                    $member = $this->netid;
                }
                $this->prop->cms()->helper('notifications')->notice("Reviewer #$rid is $member");
            }
        }
        $out .= '</h3>';
        $out .= "<table style='width:100%'>";
        $out .= "<tr><th colspan=2>Ratings</th></tr>";
        $out .= $this->multipleChoiceHTML('significance', 'Significance and innovation');
        $out .= $this->multipleChoiceHTML('potential', 'Methods, work plan, and potential for completion');
        $out .= $this->multipleChoiceHTML('clarity', 'Clarity');
        $out .= $this->multipleChoiceHTML('impacts', 'Broader impacts');
        $out .= $this->multipleChoiceHTML('budget', 'Budget and budget justification');
        if (($notes = trim($this->notesHR())) && ($notes != '<p></p>')) {
            $out .= "<tr><th colspan=2>Notes</th></tr>";
            $out .= "<tr><td colspan=2 style='background:#fff'>$notes</td></tr>";
        }
        $out .= "</table>";
        return $out;
    }

    /**
     * Return the score of this rating based on quantifiable rating questions.
     *
     * @return ?int
     */
    public function quantScore(): ?int
    {
        if (!$this->data()) {
            return null;
        }
        return ($this->quantRubricScore() + $this->quantPriorityScore()) / 2;
    }

    public function scoreHR(): string
    {
        return
        // "Overall: " . round($this->quantScore()) . "/100<br>" .
        "Rubric: " . round($this->rubricScore()) . "/100<br>" .
        "Priority: " . round($this->priorityScore()) . '/45';
    }

    protected function rubricScore(): ?int
    {
        $score = 0;
        foreach ($this->data()['ratings'] as $rating) {
            if ($rating > 0) {
                $score += $rating * 4;
            }
        }
        return $score;
    }

    protected function quantRubricScore(): ?int
    {
        return $this->rubricScore();
    }

    protected function quantPriorityScore(): ?int
    {
        return $this->priorityScore() / 45 * 100;
    }

    protected function priorityScore(): int
    {
        $score = 0;
        $score += $this->data()['priority']['assistant'] ? 10 : 0;
        $score += $this->data()['priority']['smallgrants'] ? 5 : 0;
        $score += $this->data()['priority']['newdirection'] * 2;
        $score += $this->data()['priority']['limitedfunding'] * 2;
        $score += $this->data()['priority']['likelyfunding'] * 2;
        return $score;
    }

    protected function multipleChoiceHTML($name, $label)
    {
        $names = [
            0 => '[not rated or not applicable]',
            1 => '1 - Poor',
            2 => '2 - Inadequate',
            3 => '3 - Adequate',
            4 => '4 - Very Good',
            5 => '5 - Exceptional',
        ];
        $rated = $names[intval($this->data()['ratings'][$name])];
        $out = '<tr class="rac-rating-row rac-rating-row-' . $this->data()['ratings'][$name] . '">';
        $out .= '<td width="50%">' . $label . '</td>';
        $out .= '<td>';
        $out .= $rated;
        $out .= '</td>';
        $out .= '</tr>';
        return $out;
    }

    public function form()
    {
        $form = new \Formward\Form('');

        $form['fundable'] = new \Formward\Fields\Select('Is this proposal compliant with RAC guidelines?');
        $form['fundable']->options([
            'yes' => 'Yes',
            'no' => 'No',
        ]);
        $form['fundable']->required(true);

        $form['ratings'] = new \Formward\Fields\Container('Rubric Score');
        $form['ratings']['significance'] = $this->multipleChoiceField('Significance and innovation');
        $form['ratings']['potential'] = $this->multipleChoiceField('Methods, work plan, and potential for completion');
        $form['ratings']['clarity'] = $this->multipleChoiceField('Clarity');
        $form['ratings']['impacts'] = $this->multipleChoiceField('Broader impacts');
        $form['ratings']['budget'] = $this->multipleChoiceField('Budget and budget justification');

        $form['priority'] = new Container('Priority Score');
        $form['priority']['assistant'] = new Checkbox('Proposal by Assistant Professor');
        $form['priority']['smallgrants'] = new Checkbox('Small Grants');
        $form['priority']['newdirection'] = $this->multipleChoicePriorityField('New Research / Creative Direction');
        $form['priority']['limitedfunding'] = $this->multipleChoicePriorityField('Fields with limited Extramural Funding');
        $form['priority']['likelyfunding'] = $this->multipleChoicePriorityField('New projects that have been favorably peer-reviewed by an extramural funding agency but were not funded yethave a strong probability for eventual funding');

        $form['comments'] = new \Digraph\Forms\Fields\ContentDefault('Comments', null, null, $this->prop->cms());
        $form['comments']->extra([]);
        $form['comments']->filter('markdown-safe');
        $form['comments']->addTip('Comments entered here <strong>will be visible to the proposal author.</strong>');

        $form->default($this->data());
        if ($form->handle()) {
            $this->data($form->value());
        }

        return $form;
    }

    protected function multipleChoicePriorityField($label)
    {
        $field = new \Formward\Fields\Select($label);
        $field->options([
            1 => '1 - Poor',
            2 => '2 - Inadequate',
            3 => '3 - Adequate',
            4 => '4 - Very Good',
            5 => '5 - Exceptional',
        ]);
        $field->required(true);
        return $field;
    }

    protected function multipleChoiceField($label)
    {
        $field = new \Formward\Fields\Select($label);
        $field->options([
            0 => '[not rated or not applicable]',
            1 => '1 - Poor',
            2 => '2 - Inadequate',
            3 => '3 - Adequate',
            4 => '4 - Very Good',
            5 => '5 - Exceptional',
        ]);
        $field->required(true);
        return $field;
    }
}
