<?php
namespace Digraph\Modules\rac_admin\Ratings;

class Rating2019 extends AbstractRating
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
        $out .= $this->multipleChoiceHTML('research', 'Research and innovation');
        $out .= $this->multipleChoiceHTML('potential', 'Methods, work plan, and potential for completion');
        $out .= $this->multipleChoiceHTML('clarity', 'Clarity of presentation');
        $out .= $this->multipleChoiceHTML('budget', 'Budget justification');
        $out .= $this->multipleChoiceHTML('impacts', 'Broader impacts');
        if (($notes = trim($this->notesHR())) && ($notes != '<p></p>')) {
            $out .= "<tr><th colspan=2>Notes</th></tr>";
            $out .= "<tr><td colspan=2 style='background:#fff'>$notes</td></tr>";
        }
        $out .= "</table>";
        return $out;
    }

    protected function multipleChoiceHTML($name, $label)
    {
        $names = [
            0 => '[not rated]',
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Very Good',
            5 => 'Excellent',
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

        $form['ratings'] = new \Formward\Fields\Container('Ratings');
        $form['ratings']['research'] = $this->multipleChoiceField('Research and innovation');
        $form['ratings']['potential'] = $this->multipleChoiceField('Methods, work plan, and potential for completion');
        $form['ratings']['clarity'] = $this->multipleChoiceField('Clarity of presentation');
        $form['ratings']['budget'] = $this->multipleChoiceField('Budget justification');
        $form['ratings']['impacts'] = $this->multipleChoiceField('Broader impacts');

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

    protected function multipleChoiceField($label)
    {
        $field = new \Formward\Fields\Select($label);
        $field->options([
            0 => '-- not rated --',
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Very Good',
            5 => 'Excellent',
        ]);
        $field->required(true);
        return $field;
    }
}
