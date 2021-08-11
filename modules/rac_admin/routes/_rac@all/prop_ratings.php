<?php
$package->cache_noStore();

$rac = $cms->helper('rac');
echo $rac->semesterForm();
$semester = $rac->workingSemesterName();
$calls = $rac->workingCalls();
$netid = $cms->helper('users')->user()->identifier();
$myAssignments = [];

foreach ($calls as $call) {
    $count = 0;
    echo "<h2>".$call->name()."</h2>";
    foreach ($rac->userAssignments($call, $netid) as $type => $props) {
        if (!$props) {
            continue;
        }
        echo "<h2>Assigned to me: $type</h2>";
        echo "<table style='width:100%'>";
        echo "<tr><th width='40%'>Proposal</th><th>Ratings</th></tr>";
        foreach ($props as $prop) {
            $myAssignments[] = $prop['dso.id'];
            $parts = $prop->parts();
            $chunks = $parts->chunks();
            echo "<tr>";
            //submission info
            echo "<td valign='top'>";
            echo $prop->infoCell();
            echo "</td>";
            //rating info
            echo "<td valign='top'>";
            ratingsCell($cms->helper('racratings')->ratings($prop));
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

echo "<h2>Not assigned to me</h2>";
echo "<table style='width:100%'>";
echo "<tr><th width='40%'>Proposal</th><th>Ratings</th></tr>";
foreach ($call->completeSubmissions() as $prop) {
    if (in_array($prop['dso.id'], $myAssignments)) {
        continue;
    }
    $myAssignments[] = $prop['dso.id'];
    $parts = $prop->parts();
    $chunks = $parts->chunks();
    echo "<tr>";
    //submission info
    echo "<td valign='top'>";
    echo $prop->infoCell();
    echo "</td>";
    //rating info
    echo "<td valign='top'>";
    ratingsCell($cms->helper('racratings')->ratings($prop));
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

function ratingsCell($ratings)
{
    foreach ($ratings as $rating) {
        echo $rating->body(true);
    }
}
