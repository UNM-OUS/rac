<?php
$package->cache_noStore();

$rac = $cms->helper('rac');
echo $rac->semesterForm();
$semester = $rac->workingSemesterName();
$calls = $rac->workingCalls();

$sortAndFilter = $cms->helper('forms')->form('Filter results');
$sortAndFilter->addClass('autosubmit compact-form');
$sortAndFilter['filter'] = $cms->helper('forms')->field('select', 'Only display proposals that are:');
$sortAndFilter['filter']->required(true);
$sortAndFilter['filter']->options([
    'none' => '-- Display all proposal records --',
    'complete' => 'Completed',
    'incomplete' => 'Incomplete',
    'funded' => 'Approved for funding',
    'notfunded' => 'Denied for funding',
]);
$sortAndFilter['filter']->default('complete');

switch ($sortAndFilter['filter']->value()) {
    case 'complete':
        $filter = function ($prop) {
            return $prop->complete();
        };
        break;
    case 'incomplete':
        $filter = function ($prop) {
            return !$prop->complete();
        };
        break;
    case 'funded':
        $filter = function ($prop) {
            return $prop->finalDecision() && $prop->finalDecision()->funded();
        };
        break;
    case 'notfunded':
        $filter = function ($prop) {
            return !$prop->finalDecision() || !$prop->finalDecision()->funded();
        };
        break;
    default:
        $filter = function ($prop) {
            return true;
        };
        break;
}

echo "<h2>$semester</h2>";
$props = array_filter(
    $cms->helper('rac')->workingProps(),
    $filter
);

echo "<h3>Proposal statistics</h3>";
echo $cms->helper('rac')->propStatsTable($cms->helper('rac')->workingProps());

echo "<h3>Proposal list</h3>";
echo $sortAndFilter;

echo "<table style='width:100%'>";
echo "<tr><th>Proposal</th><th>Call</th><th>Decision</th></tr>";
foreach ($props as $prop) {
    echo "<tr>";
    //submission info
    echo "<td valign='top'>";
    echo $prop->infoCell();
    echo "</td>";
    //call
    echo "<td valign='top'>";
    echo $prop->window()->link();
    echo "</td>";
    //decision
    echo "<td valign='top'>";
    if ($decision = $prop->decision()) {
        echo $decision->info();
    } else {
        echo "<em>none</em>";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
