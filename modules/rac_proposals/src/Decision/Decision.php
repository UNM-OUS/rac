<?php
namespace Digraph\Modules\rac_proposals\Decision;

class Decision extends \Digraph\DSO\Noun
{
    const SLUG_ID_LENGTH = 4;

    public function name($verb=null)
    {
        return "Decision #".$this['dso.id'];
    }

    public function info()
    {
        $out = '<div class="digraph-block">';
        $out .= '<strong>'.$this->fundedHR().'</strong>';
        $out .= '<br><em><a href="'.$this->url().'">Decision #'.$this['dso.id'].'</a></em>';
        $out .= '</div>';
        return $out;
    }

    public function funded()
    {
        return $this['funded'];
    }

    public function fundedHR()
    {
        if ($this->funded()) {
            return '$'.number_format($this->funded());
        } else {
            return '[not funded]';
        }
    }

    public function prop()
    {
        return $this->cms()->helper('graph')->nearest($this['dso.id'], 'proposal');
    }

    public function parentEdgeType($parent)
    {
        if ($parent['dso.type'] == 'proposal') {
            return 'proposal-decision';
        }
        return null;
    }

    public function formMap(string $action) : array
    {
        $map = parent::formMap($action);
        $map['funded'] = [
            'class' => \Formward\Fields\Number::class,
            'label' => 'Amount to fund',
            'field' => 'funded',
            'tips' => [
                'zero' => "Enter zero or leave blank to deny this proposal."
            ]
        ];
        // hide most things
        $map['digraph_title'] = false;
        $map['digraph_name'] = false;
        // set up user friendly content field
        $map['digraph_body']['label'] = 'Notes for submitter';
        $map['digraph_body']['class'] = 'digraph_content_default';
        $map['digraph_body']['call'] = [
            'extra' => [[]],
            'filter' => ['markdown-safe']
        ];
        return $map;
    }
}
