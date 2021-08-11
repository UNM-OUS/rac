<?php
namespace Digraph\Modules\rac_proposals;

class ProposalCall extends \Digraph\Modules\Submissions\SubmissionWindow
{
    const SLUG_ID_LENGTH = 4;

    public function submissionType()
    {
        return 'proposal';
    }

    public function name($verb=null)
    {
        if ($this['digraph.name']) {
            return parent::name($verb);
        }
        return $this['semester.semester'].' '.$this['semester.year'].' call for proposals';
    }

    public function parentEdgeType($parent)
    {
        if ($parent['dso.type'] == 'proposal-group') {
            return 'proposal-call';
        }
        return null;
    }

    public function slugVars()
    {
        return [
            'semester' => $this['semester.semester'],
            'year' => $this['semester.year'],
        ];
    }

    public function formMap(string $action) : array
    {
        $s = $this->cms()->helper('strings');
        $map = parent::formMap($action);
        // Change weight of name
        $map['digraph_name']['weight'] = 110;
        $map['digraph_name']['required'] = false;
        // semester and year
        $map['call_semester'] = [
            'label' => 'Year/Semester',
            'class' => '\\Digraph\\Modules\\ous_digraph_module\\Fields\\SemesterField',
            'required' => true,
            'field' => 'semester',
            'weight' => 0,
            'tips' => [
                'Used to name this call for submissions and to place proposals in the correct academic year for report generation.'
            ]
        ];
        // add fields to specify minimum/maximum amount requested
        $map['requestamount_minimum'] = [
            'label' => 'Minimum amount to request',
            'class' => '\\Formward\\Fields\\Number',
            'required' => true,
            'field' => 'requestamount.minimum',
            'weight' => 50
        ];
        $map['requestamount_maximum'] = [
            'label' => 'Maximum amount to request',
            'class' => '\\Formward\\Fields\\Number',
            'required' => true,
            'field' => 'requestamount.maximum',
            'weight' => 50
        ];
        // hide title
        $map['digraph_title'] = false;
        return $map;
    }
}
