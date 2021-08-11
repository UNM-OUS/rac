<?php
$package->cache_noStore();

$rac = $cms->helper('rac');
echo $rac->semesterForm();
$semester = $rac->workingSemesterName();
$calls = $rac->workingCalls();

$sortAndFilter = $cms->helper('forms')->form('');
$sortAndFilter->addClass('autosubmit compact-form');
$sortAndFilter['filter'] = $cms->helper('forms')->field('select', 'Only display proposals that are:');
$sortAndFilter['filter']->required(true);
$sortAndFilter['filter']->options([
    'none' => '-- Display all proposal records --',
    'complete' => 'Completed',
    'incomplete' => 'Incomplete',
    'funded' => 'Approved for funding',
    'notfunded' => 'Denied for funding'
]);
$sortAndFilter['filter']->default('none');
echo $sortAndFilter;

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

$props = array_filter(
    $cms->helper('rac')->workingProps(),
    $filter
);

usort($props, function ($a, $b) {
    return strcmp(
        strtolower($a['submitter.lastname'].$a['submitter.firstname']),
        strtolower($b['submitter.lastname'].$b['submitter.firstname'])
    );
});

echo "<h2>$semester submitters</h2>";
echo "<table>";
echo "<tr><th>Name</th><th>School/college</th><th>Department</th></tr>";
foreach ($props as $prop) {
    echo "<tr>";
    echo "<td>".$prop['submitter.firstname']." ".$prop['submitter.lastname']."</td>";
    echo "<td>".$prop['submitter.college']."</td>";
    echo "<td>".$prop['submitter.department']."</td>";
    echo "</tr>";
}
echo "</table>";
