<?php
$package->cache_noStore();

$rac = $cms->helper('rac');
echo $rac->semesterForm();
$semester = $rac->workingSemesterName();
$calls = $rac->workingCalls();
$netid = $cms->helper('users')->user()->identifier();
echo "<hr>";

$ratings = $cms->helper('racratings');
$props = array_filter(
    $rac->workingProps(),
    function ($prop) {
        return $prop->complete();
    }
);
usort(
    $props,
    function ($a, $b) use ($ratings) {
        $a = $ratings->fundabilityScore($a);
        $b = $ratings->fundabilityScore($b);
        if ($a == $b) {
            return 0;
        }
        return ($a < $b) ? +1 : -1;
    }
);

echo "<h3>Proposal statistics</h3>";
echo "<p>Funding decisions are not visible to proposers until the <a href='".$this->url('_rac_chair', 'finalize')."'>funding finalization tool</a> is run.</p>";
echo $cms->helper('rac')->propStatsTable($props, true);

echo "<h3>Proposal list</h3>";

echo "<table style='width:100%'>";
echo "<tr><th>Proposal</th><th>Overall Score</th><th>Ratings</th><th>Decision</th></tr>";
foreach ($props as $prop) {
    if ($decision = $prop->decision()) {
        if ($decision->funded()) {
            echo "<tr class='highlighted-confirmation'>";
        } else {
            echo "<tr class='highlighted-warning'>";
        }
    } else {
        echo "<tr class='highlighted'>";
    }
    //submission info
    echo "<td valign='top'>";
    echo $prop->infoCell();
    echo "</td>";
    //fundability score
    echo "<td valign='top'>";
    echo '<p>'.$ratings->fundabilityScore($prop).'</p>';
    echo "</td>";
    //ratings
    echo "<td valign='top' style='white-space:nowrap;'>";
    $prs = $ratings->ratings($prop);
    if (!$prs) {
        echo "<strong>No ratings</strong>";
    }
    foreach ($prs as $n => $r) {
        if ($r->data()) {
            if ($r->member()) {
                $name = $r->member()->name();
            } else {
                $name = $r->netid();
            }
            echo "<p>";
            echo "<strong>".$name."</strong><br>";
            echo "Compliant: ".($r->fundable()?'Yes':'No').'<br>';
            echo $r->scoreHR();
            echo "<div><a href='".$this->url('_rac_chair', 'rating_edit', [
                    'prop' => $prop['dso.id'],
                    'netid' => $r->netid()
                ])."'>view/edit</a></div>";
            echo '</p>';
        }
    }
    echo '</td>';
    //decision
    echo "<td valign='top' style='white-space:nowrap;'>";
    if ($decision) {
        echo $decision->info();
        if (!$prop->finalDecision()) {
            echo "<a href='".$this->url('_rac_chair', 'decision', ['prop'=>$prop['dso.id']])."'>[edit decision]</a>";
        } else {
            echo '[finalized]';
        }
    } else {
        echo "<a href='".$this->url('_rac_chair', 'decision', ['prop'=>$prop['dso.id']])."'>[add decision]</a>";
    }
    echo "</td>";
}
echo "</table>";
