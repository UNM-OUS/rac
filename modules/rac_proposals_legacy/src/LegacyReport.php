<?php
namespace Digraph\Modules\rac_proposals_legacy;

use Digraph\Modules\rac_proposals\Report\Report2019;

class LegacyReport extends Report2019
{
    public function reportType()
    {
        return 'proposal-report-legacy';
    }

    public function formMap(string $action) : array
    {
        $map = parent::formMap($action);
        $map['expenditures_total'] = false;
        $map['expenditures'] = false;
        $map['body'] = false;
        $map['presentations'] = false;
        $map['papers'] = false;
        $map['grants'] = false;
        $map['publications'] = false;
        //just one upload field
        $map['report'] = [
            'weight' => 100,
            'label' => 'Report PDF',
            'field' => 'report',
            'class' => '\\Digraph\\Modules\\rac_proposals\\Fields\FilePDF',
            'required' => true
        ];
        return $map;
    }
}
