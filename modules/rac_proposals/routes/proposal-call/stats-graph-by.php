<?php
$call = $package->noun();
$package['response.ttl'] = 3600*8;

$LABELS = [
    'submitter.rank' => 'Submitter faculty rank',
    'submitter.yearsvoting' => 'Submitter years voting faculty',
    'submission.discipline' => 'Submission discipline',
    'submitter.college' => 'Submitter school/college',
    'submission.requested' => 'Amount requested'
];

$SORT = [
    'submitter.yearsvoting' => 'key',
    'submission.requested' => 'key'
];

$TRANSFORMS = [
    'submitter.yearsvoting' => function ($value) {
        $g = 5;
        $bottom = floor($value/$g)*$g;
        $top = ceil($value/$g)*$g;
        if ($bottom == $top) {
            $top += $g;
        }
        $top--;
        return str_pad($bottom, 6, '0', STR_PAD_LEFT)."|$bottom-$top";
    },
    'submission.requested' => function ($value) {
        $g = 1000;
        $bottom = floor($value/$g)*$g;
        $top = ceil($value/$g)*$g;
        if ($bottom == $top) {
            $bottom -= $g;
        }
        $bottom++;
        return str_pad($bottom, 6, '0', STR_PAD_LEFT)."|$".number_format($bottom)."-".number_format($top);
    }
];

$BY = $package['url.args.by'];
if (!isset($LABELS[$BY])) {
    $package->error(404);
    return;
}

$DATA = [];

foreach ($call->completeSubmissions() as $prop) {
    $value = $prop[$BY];
    //transform
    if (isset($TRANSFORMS[$BY])) {
        $value = $TRANSFORMS[$BY]($value);
    }
    //split data up
    if (!isset($DATA[$value])) {
        $DATA[$value] = [
            'awarded' => 0,
            'denied' => 0,
            'no decision' => 0
        ];
    }
    $segment = 'no decision';
    if ($prop->finalDecision()) {
        $segment = $prop->decision()->funded()?'awarded':'denied';
    }
    $DATA[$value][$segment]++;
}

if (@$SORT[$BY] == 'key') {
    krsort($DATA);
} else {
    uasort(
        $DATA,
        function ($a, $b) {
            $sa = $a['awarded']+$a['denied']+$a['no decision'];
            $sb = $b['awarded']+$b['denied']+$b['no decision'];
            if ($sa == $sb) {
                if ($a['awarded'] == $b['awarded']) {
                    return 0;
                }
                return ($a['awarded']>$b['awarded'])?1:-1;
            }
            return ($sa>$sb)?1:-1;
        }
    );
}

$package->makeMediaFile('stats.'.$BY.'.svg');

$graph = new Goat1000\SVGGraph\SVGGraph(1200, (count($DATA)+1)*100, [
    'graph_title' => $LABELS[$BY],
    'auto_fit' => true,
    'show_axis_h' => false,
    'show_axis_text_h' => false,
    'graph_title_font_size' => 24,
    'tooltip_font_size' => 24,
    'axis_font_size' => 24
]);

//make data how SVGGraph needs it
$VALUES = [[],[],[],[]];
foreach ($DATA as $value => $segments) {
    $value = preg_replace('/^[0-9]+\|/', '', $value);
    $value = str_replace(' ', "\n", $value);
    $value = preg_replace('/[\n](\&|of|and)/i', ' $1', $value);
    $VALUES[0][$value] = $segments['awarded'];
    $values[2][$value] = 0;//dummy to get no decision on the right
    $VALUES[1][$value] = $segments['no decision'];
    $VALUES[3][$value] = $segments['denied'];
}
$graph->values($VALUES);

//pull colors from theme
$v = $cms->helper('templates')->variables();
$graph->colours([
    $v['color_confirmation'],
    $v['color_notice'],
    $v['color_notice'],
    $v['color_error']
]);

//render graph
$graph->render('PopulationPyramid');
