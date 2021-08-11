<?php
namespace Digraph\Modules\rac_proposals\Report;

class Report2019 extends \Digraph\DSO\Noun
{
    const SLUG_ID_LENGTH = 4;
    const FILESTORE = true;

    public function hook_postEditUrl()
    {
        return $this->url()->string();
    }

    public function hook_postAddUrl()
    {
        return $this->url()->string();
    }

    public function body()
    {
        $f = $this->cms()->helper('filters');
        ob_start();

        echo "<h2>Proposal information</h2>";
        $parts = $this->prop()->parts();
        $chunks = $parts->chunks();
        $this->cms()->helper('notifications')->printConfirmation('Awarded '.$this->prop()->finalDecision()->fundedHR().'<br>'.$this->prop()->window()->name());
        echo $chunks['submitter']->body();
        echo $chunks['submission']->body();

        echo "<h2>Expenditures</h2>";
        echo "<p>Total actual expenditures: $".number_format($this['expenditures_total'])."</p>";
        $fs = $this->cms()->helper('filestore');
        foreach ($fs->list($this, 'expenditures') as $file) {
            echo $file->metacard();
        }

        echo "<h2>Presentations</h2>";
        echo $f->filterContentField(
            $this['presentations'],
            $this['dso.id']
        );

        echo "<h2>Publications</h2>";
        echo $f->filterContentField(
            $this['publications'],
            $this['dso.id']
        );

        echo "<h2>Grants/proposals</h2>";
        echo $f->filterContentField(
            $this['grants'],
            $this['dso.id']
        );

        echo "<h2>Body</h2>";
        echo $f->filterContentField(
            $this['body'],
            $this['dso.id']
        );

        $out = ob_get_contents();
        ob_end_clean();
        return $out;
    }
    
    public function reportType()
    {
        return 'proposal-report-2019';
    }
    
    public function name($verb=null)
    {
        return "Final report #".$this['dso.id'];
    }

    public function prop()
    {
        return $this->cms()->helper('graph')->nearest($this['dso.id'], 'proposal');
    }

    public function parentEdgeType($parent)
    {
        if ($parent['dso.type'] == 'proposal') {
            return 'proposal-report';
        }
        return null;
    }

    public function formMap(string $action) : array
    {
        $map = parent::formMap($action);
        //hide what we don't need
        $map['digraph_name'] = false;
        $map['digraph_title'] = false;
        $map['digraph_body'] = false;
        //set up our fields
        $map['expenditures_total'] = [
            'weight' => 101,
            'label' => 'Total actual expenditures',
            'tips' => [
                'Any unused balance will be returned to the RAC funding index after the 18 month award period.'
            ],
            'field' => 'expenditures_total',
            'class' => '\\Formward\\Fields\\Number',
            'required' => true
        ];
        $map['expenditures'] = [
            'weight' => 100,
            'label' => 'Itemized list of actual expenditures',
            'extraConstructArgs' => ['expenditures'],
            'class' => '\\Digraph\\Modules\\rac_proposals\\Fields\FilePDF',
            'required' => true
        ];
        $map['body'] = [
            'weight' => 300,
            'label' => 'Body of report',
            'tips' => [
                'Start with a summary or abstract followed by a brief description of the major accomplishments resulting from this support.'
            ],
            'field' => 'body',
            'class' => 'digraph_content_default',
            'required' => true,
            'call' => [
                'extra' => [[]],
                'filter' => ['markdown-safe']
            ]
        ];
        $map['presentations'] = [
            'label' => 'Presentations',
            'tips' => ['Exhibits, products, patents, etc resulting from this support.'],
            'field' => 'presentations',
            'class' => 'digraph_content_default',
            'required' => true,
            'weight' => 201,
            'call' => [
                'extra' => [[]],
                'filter' => ['markdown-safe']
            ]
        ];
        $map['publications'] = [
            'label' => 'Publications',
            'tips' => ['Published, in press, or submitted.'],
            'field' => 'publications',
            'class' => 'digraph_content_default',
            'required' => true,
            'weight' => 202,
            'call' => [
                'extra' => [[]],
                'filter' => ['markdown-safe']
            ]
        ];
        $map['grants'] = [
            'label' => 'Grants/proposals',
            'tips' => ['Outside funding applied for or received as a result of this support.'],
            'field' => 'grants',
            'class' => 'digraph_content_default',
            'required' => true,
            'weight' => 203,
            'call' => [
                'extra' => [[]],
                'filter' => ['markdown-safe']
            ]
        ];
        //return
        return $map;
    }
}
