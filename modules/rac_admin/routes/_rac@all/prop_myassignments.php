<?php
$package->cache_noStore();

$rac = $cms->helper('rac');
echo $rac->semesterForm();
$semester = $rac->workingSemesterName();
$calls = $rac->workingCalls();
$netid = $cms->helper('users')->user()->identifier();

foreach ($calls as $call) {
    $count = 0;
    echo "<h2>".$call->name()."</h2>";
    $locked = $rac->lockedAssignments($call);
    if (!$locked) {
        $cms->helper('notifications')->printNotice("<p>Assignments are in a draft state. Please notify the chair as soon as possible of any proposals you need to recuse yourself from or are otherwise unable to review.</p>");
    }
    foreach ($rac->userAssignments($call, $netid) as $type => $props) {
        if (!$props) {
            continue;
        }
        echo "<h3>$type</h3>";
        echo "<table style='width:100%'>";
        echo "<tr><th width='40%'>Proposal</th><th width='40%'>Proposer</th><th>My rating</th></tr>";
        foreach ($props as $prop) {
            $count++;
            $parts = $prop->parts();
            $chunks = $parts->chunks();
            $rating = $cms->helper('racratings')->rating($prop, $netid);
            if ($rating->data()) {
                echo "<tr class='highlighted-confirmation'>";
            } else {
                echo "<tr class='highlighted-warning'>";
            }
            //submission info
            echo "<td valign='top'>";
            echo $prop->infoCell();
            echo "</td>";
            //applicant info
            echo "<td valign='top'>";
            echo $chunks['submitter']->body_complete();
            echo "</td>";
            //rating info
            echo "<td valign='top'>";
            if ($locked) {
                $url = $this->url('_rac', 'prop_rate', ['prop'=>$prop['dso.id']]);
                if ($rating->data()) {
                    echo "Compliant: ".($rating->fundable()?'Yes':'No').'<br>';
                    echo $rating->scoreHR();
                    echo "<div><a href='$url'>update rating</a></div>";
                } else {
                    echo "<a href='$url'>add rating</a>";
                }
            } else {
                echo "<em>Reviews cannot be posted yet</em>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    if (!$count) {
        $cms->helper('notifications')->printNotice('No review assignments have been made for you.');
    }
}
