<?php
$package->cache_noStore();

$rac = $cms->helper('rac');
echo $rac->propWindowForm();
$semester = $rac->workingSemesterName();
$calls = $rac->workingCalls();
$netid = $cms->helper('users')->user()->identifier();

$totalAssignments = 0;
$completeAssignments = 0;

foreach ($calls as $call) {
    $proposals = $call->completeSubmissions();
    $datastore = $rac->windowDatastore($call);
    $assignments = $datastore->get('assignments');
    $ratings = $cms->helper('racratings');

    $byUser = [];

    foreach ($proposals as $prop) {
        if (!$assignments[$prop['dso.id']]) {
            continue;
        }
        foreach ($assignments[$prop['dso.id']] as $type => $ns) {
            foreach ($ns as $netid) {
                $totalAssignments++;
                $rating = $ratings->rating($prop, $netid);
                if ($rating->data()) {
                    $completeAssignments++;
                }
                $byUser[$netid][$prop['dso.id']] = $rating;
            }
        }
    }
}

//sort $byUser by number of unfinished assignments, to put more incomplete people at the top
uasort(
    $byUser,
    function ($a,$b) use ($ratings) {
        $ai = 0;
        $bi = 0;
        foreach ($a as $r) {
            if (!$r->data()) {
                $ai++;
            }
        }
        foreach ($b as $r) {
            if (!$r->data()) {
                $bi++;
            }
        }
        if ($ai == $bi) {
            return 0;
        }
        return ($ai < $bi) ? +1 : -1;
    }
);

echo "<p>$completeAssignments/$totalAssignments rating assignments finished.</p>";
if ($completeAssignments == $totalAssignments) {
    return;
}

echo '<h2>Unfinished assignments</h2>';

foreach ($byUser as $netid => $rs) {
    if ($member = $rac->member($netid)) {
        $member = $member->name();
    }else {
        $member = $netid;
    }
    $incomplete = 0;
    foreach ($rs as $r) {
        if (!$r->data()) {
            $incomplete++;
        }
    }
    if ($incomplete) {
        echo "<h3>$member ($incomplete incomplete)</h3>";
    }
    echo "<ul>";
    foreach ($rs as $propid => $r) {
        if (!$r->data()) {
            $prop = $cms->read($propid);
            echo "<li>".$prop->name()."</li>";
        }
    }
    echo "</ul>";
}
